<?php

declare(strict_types=1);

namespace Himbeer\HideCommands;

use pocketmine\event\Listener;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Filesystem;
use Symfony\Component\Filesystem\Path;

class Main extends PluginBase implements Listener {

	/** @var string File name of the default config */
	private const DEFAULT_CONFIG_FILE_NAME = "config.yml";
	/** @var string File name of the backup that gets created by the automatic config update */
	private const BACKUP_CONFIG_FILE_NAME = "config_backup_v2.yml";

	public function onEnable() : void {
		$this->saveDefaultConfig();

		if ($this->getConfig()->get("version", null) === null) {
			if (!$this->updateConfig()) {
				throw new DisablePluginException();
			}
		}

		$perWorldSettingsEnabled = false;
		$perWorldSettings = [];
		try {
			$defaultSettings = new Settings($this->getConfig()->get("default"));
			$perWorldConfigs = $this->getConfig()->get("per-world");
			if ($perWorldConfigs) {
				$perWorldSettingsEnabled = true;
				foreach ($perWorldConfigs as $worldName => $perWorldConfig) {
					$perWorldSettings[$worldName] = new Settings($perWorldConfig);
				}
			}
		} catch (InvalidModeException) {
			$this->getLogger()->error('Invalid mode selected, must be either "blacklist" or "whitelist"! Disabling...');
			throw new DisablePluginException();
		}

		if ($perWorldSettingsEnabled) {
			$this->getServer()->getPluginManager()->registerEvents(new ListenerPerWorld($this, $defaultSettings, $perWorldSettings), $this);
		} else {
			$this->getServer()->getPluginManager()->registerEvents(new ListenerDefault($this, $defaultSettings), $this);
		}
	}

	/**
	 * @return bool True if the config was updated successfully
	 */
	private function updateConfig() : bool {
		$this->getLogger()->notice("Config version is outdated, updating automatically...");
		$mode = $this->getConfig()->get("mode", null);
		if ($mode !== "whitelist" && $mode !== "blacklist") {
			$this->getLogger()->error("Could not update config file: Mode is invalid!");
			return false;
		}
		$commands = $this->getConfig()->get("commands", null);
		if ($commands === null) {
			$this->getLogger()->error("Could not update config file: Commands are missing!");
			return false;
		}

		$configPath = Path::join($this->getDataFolder(), self::DEFAULT_CONFIG_FILE_NAME);
		$configBackupPath = Path::join($this->getDataFolder(), self::BACKUP_CONFIG_FILE_NAME);
		if (!rename($configPath, $configBackupPath)) {
			$this->getLogger()->error("Could not update config file: Could not rename config.yml to config_backup_v2.yml");
			return false;
		}

		$configResource = $this->getResource(self::DEFAULT_CONFIG_FILE_NAME);
		$configContent = stream_get_contents($configResource);
		fclose($configResource);
		if ($configContent === false) {
			$this->getLogger()->error("Could not update config file: Could not read new config");
			return false;
		}

		$configContent = str_replace('mode: "whitelist"', "mode: \"$mode\"", $configContent);

		$commandsYaml = yaml_emit($commands, YAML_UTF8_ENCODING);
		$commandsYaml = substr($commandsYaml, 3, -4); // Remove --- and ...
		$commandsYaml = str_replace("\n", "\n    ", $commandsYaml);
		$configContent = preg_replace('/commands:.*?##/s', "commands:$commandsYaml\n##", $configContent);

		Filesystem::safeFilePutContents($configPath, $configContent);

		$this->reloadConfig();

		$this->getLogger()->notice("Config version updated successfully!");
		$this->getLogger()->notice("You can now use the HideCommands per-world feature!");
		$this->getLogger()->info("A backup of your current configuration has been saved with the name " . self::BACKUP_CONFIG_FILE_NAME);
		return true;
	}
}
