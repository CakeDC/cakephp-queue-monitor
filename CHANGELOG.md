# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2024-12-10
* added commands to purge queues and test queues
* fixed test queue template path
* changed `queue_monitor purge` to `queue-monitor purge-logs`
* changed `queue_monitor notify` to `queue-monitor notify
* changed default configuration of `purgeLogsOlderThanDays` to 7 days instead of 30

## [2.0.2] - 2024-12-09
* ported fixes and enhancement from version 1.x
* fixed handling `QueueMonitor.purgeLogsOlderThanDays` in Purge command
* added `QueueMonitor.disabled` option
* added support for disabling queue monitor commands
* updated README.md
* decreased log level when queue monitor is disabled
* replaced saveOrFail with save

## [2.0.1] - 2024-05-14
### Fixed
- Removed TimestampBehavior from LogsTable to avoid problems when TimestampBehavior is overriden

## [2.0] - 2024-04-17

### Added

- First release for CakePHP 5.0

## [1.0] - 2024-04-17

### Added

- First release for CakePHP 4.4
