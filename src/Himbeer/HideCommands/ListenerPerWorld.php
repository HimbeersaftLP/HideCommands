<?php

declare(strict_types=1);

namespace Himbeer\HideCommands;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;

/**
 * This Listener is used instead of @see ListenerDefault when per-world settings are defined
 */
class ListenerPerWorld implements Listener {
	private Main $plugin;

	/** @var Settings Default settings for worlds not explicitly specified */
	private Settings $defaultSettings;

	/** @var Settings[] Settings indexed by the world name */
	private array $perWorldSettings;

	/** @var string[] The target world folder name when a player teleports, indexed by the player name */
	private array $futureWorlds = [];

	public function __construct(Main $plugin, Settings $defaultSettings, array $perWorldSettings) {
		$this->plugin = $plugin;
		$this->defaultSettings = $defaultSettings;
		$this->perWorldSettings = $perWorldSettings;
	}

	/**
	 * @priority MONITOR
	 */
	public function onEntityTeleport(EntityTeleportEvent $event) {
		$player = $event->getEntity();
		if ($player instanceof Player) {
			if ($event->getFrom()->getWorld() !== $event->getTo()->getWorld()) {
				$this->futureWorlds[$player->getName()] = $event->getTo()->getWorld()->getFolderName();
				$player->getNetworkSession()->syncAvailableCommands();
			}
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) {
		$packets = $event->getPackets();
		foreach ($packets as $packet) {
			if ($packet instanceof AvailableCommandsPacket) {
				$targets = $event->getTargets();
				if (sizeof($targets) !== 1) {
					$this->plugin->getLogger()->error("AvailableCommandsPackets sent to multiple players are not supported!");
					return;
				}
				$target = $targets[0];
				$player = $target->getPlayer();
				if ($player !== null) {
					if ($player->hasPermission("hidecommands.unhide")) return;

					$worldName = $this->futureWorlds[$player->getName()] ?? $player->getWorld()->getFolderName();
					unset($this->futureWorlds[$player->getName()]);

					if (isset($this->perWorldSettings[$worldName])) {
						$this->perWorldSettings[$worldName]->applyToAvailableCommandsPacket($packet);
					} else {
						$this->defaultSettings->applyToAvailableCommandsPacket($packet);
					}
				}
			}
		}
	}
}