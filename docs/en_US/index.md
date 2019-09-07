---
layout: default
title: Index
lang: en_US
---

# Description

Plugin allowing custom log management in your scenarios.
It is possible to create as many log as wanted, different log level are possible for each log file.
This allows you to organize your scenario logs according to your preferences, for example to group all the actions on a device in the same log whatever the scenario.

The logs are consulted via Jeedom's standard interface.
The purge of the logs is also managed by Jeedom's general config.

# Installation

In order to use the plugin, you must download, install and activate it like any Jeedom plugin.

# Device configuration

The plugin is in the Plugins > Programming menu.
After creating new device, the usual options are available.

One device corresponds to one log, the name of the device will be used as the name of the log file.

> **Tip**
>
> In order to avoid potential problems, the name of the device must consist only of letters a to z, in lowercase, and the underscore character "_", the first character must be a letter.

In addition, you can select the level of log to write: Debug, Info, Warning, Error.

# Commands

Each device (log) has a message type command per log level:

- debug
- info
- warning
- error

Just call the desired command with the message and the log will be written according to the log level configured on the device.

# The widget

A standard core widget will be displayed with the selected commands (to be configured in the "Commands" page of the device).