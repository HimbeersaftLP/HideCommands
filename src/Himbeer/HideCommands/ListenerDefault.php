<?php

declare(strict_types=1);

namespace Himbeer\HideCommands;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

/**
 * This Listener is used when no per-world settings are defined
 */
class ListenerDefault implements Listener {
	private Main $plugin;

	private Settings $settings;

	public function __construct(Main $plugin, Settings $settings) {
		$this->plugin = $plugin;
		$this->settings = $settings;
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

					$this->settings->applyToAvailableCommandsPacket($packet);
				}
			}
		}
	}
}