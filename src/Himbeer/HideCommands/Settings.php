<?php

declare(strict_types=1);

namespace Himbeer\HideCommands;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

class Settings {
	private const MODE_WHITELIST = 0;
	private const MODE_BLACKLIST = 1;

	/** @var int Either MODE_WHITELIST or MODE_BLACKLIST */
	public int $mode;
	/** @var array List of commands that are hidden/shown, the commands are the array keys, all values are null */
	public array $commandList = [];

	/**
	 * @throws InvalidModeException
	 */
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

	public function applyToAvailableCommandsPacket(AvailableCommandsPacket $packet) : void {
		if ($this->mode === self::MODE_WHITELIST) {
			$packet->commandData = array_intersect_key($packet->commandData, $this->commandList);
		} else {
			$packet->commandData = array_diff_key($packet->commandData, $this->commandList);
		}
	}
}