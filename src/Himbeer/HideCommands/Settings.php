<?php

declare(strict_types=1);

namespace Himbeer\HideCommands;

use Exception;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

class InvalidModeException extends Exception {
}

class Settings {
	private const MODE_WHITELIST = 0;
	private const MODE_BLACKLIST = 1;

	/** @var int Either MODE_WHITELIST or MODE_BLACKLIST */
	public int $mode;
	/** @var string[] List of commands that are hidden/shown */
	public array $commandList = [];

	public function __construct(array $configData) {
		switch ($configData["mode"]) {
			case "whitelist":
				$this->mode = self::MODE_WHITELIST;
				break;
			case "blacklist":
				$this->mode = self::MODE_BLACKLIST;
				break;
			default:
				throw new InvalidModeException();
		}

		foreach ($configData["commands"] as $command) {
			// We put the command name in the key, so we can use array_intersect_key and array_diff_key
			$this->commandList[strtolower($command)] = null;
		}
	}

	public function applyToAvailableCommandsPacket(AvailableCommandsPacket $packet) {
		if ($this->mode === self::MODE_WHITELIST) {
			$packet->commandData = array_intersect_key($packet->commandData, $this->commandList);
		} else {
			$packet->commandData = array_diff_key($packet->commandData, $this->commandList);
		}
	}
}