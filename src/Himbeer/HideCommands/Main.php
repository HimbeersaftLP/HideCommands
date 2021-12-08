<?php

declare(strict_types=1);

namespace Himbeer\HideCommands;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	private const MODE_WHITELIST = 0;
	private const MODE_BLACKLIST = 1;

	/** @var int */
	private $mode;
	/** @var array */
	private $commandList = [];

	public function onEnable(): void {
		$this->saveDefaultConfig();

		switch ($this->getConfig()->get("mode")) {
			case "whitelist":
				$this->mode = self::MODE_WHITELIST;
				break;
			case "blacklist":
				$this->mode = self::MODE_BLACKLIST;
				break;
			default:
				$this->getLogger()->error('Invalid mode selected, must be either "blacklist" or "whitelist"! Disabling...');
				return;
		}

		foreach ($this->getConfig()->get("commands") as $command) {
			// We put the command name in the key so we can use array_intersect_key and array_diff_key
			$this->commandList[strtolower($command)] = null;
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDataPacketSend(DataPacketSendEvent $event) {
		$packets = $event->getPackets();
		foreach ($packets as $packet) {
			if ($packet instanceof AvailableCommandsPacket) {
				$targets = $event->getTargets();
				foreach ($targets as $target) {
					if ($target->getPlayer() !== null){
						if($target->getPlayer()->hasPermission("hidecommands.unhide")) return;
						if($this->mode === self::MODE_WHITELIST){
							$packet->commandData = array_intersect_key($packet->commandData, $this->commandList);
						}else{
							$packet->commandData = array_diff_key($packet->commandData, $this->commandList);
						}
					}
				}
			}
		}
	}
}
