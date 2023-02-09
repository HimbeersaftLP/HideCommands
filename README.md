# HideCommands <img alt="Plugin Logo/Icon" src="https://raw.githubusercontent.com/HimbeersaftLP/HideCommands/master/icon.png" height="45">

[![Poggit CI Status](https://poggit.pmmp.io/ci.shield/HimbeersaftLP/HideCommands/HideCommands)](https://poggit.pmmp.io/ci/HimbeersaftLP/HideCommands/HideCommands)
[![Poggit Release Status](https://poggit.pmmp.io/shield.state/HideCommands)](https://poggit.pmmp.io/p/HideCommands)
[![Join the Discord Server](https://img.shields.io/discord/252874887113342976?logo=discord)](https://www.himbeer.me/discord)

## Description

A [PocketMine-MP](https://github.com/pmmp/PocketMine-MP) plugin that removes specific commands from the in-game command suggestions.

Note: Just because the commands are hidden, doesn't mean they can't be executed. You still need to make sure to have proper permission management in place.

## Usage

1. Put phar from Poggit into `plugins` folder
2. Start server
3. Stop server
4. Edit the config.yml file located in `plugin_data/HideCommands`
    - Choose whitelist or blacklist mode
    - Select the commands to whitelist/blacklist

The permission `hidecommands.unhide` allows players to see hidden commands again.
It has been set to `default: false`, which means you need to explicitly give it to a player or group using a permission manager like [PurePerms](https://poggit.pmmp.io/p/PurePerms/). 

## Additional Information

Icon credits: https://pixabay.com/vectors/spy-hat-anony-anonymous-detect-2657484/