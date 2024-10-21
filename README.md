# CakeDC Queue Monitor Plugin for CakePHP

## Versions and branches
| CakePHP |                        CakeDC Queue Monitor Plugin                         |     Tag      | Notes  |
|:-------:|:--------------------------------------------------------------------------:|:------------:|:-------|
|  ^5.0   | [2.0.0](https://github.com/CakeDC/cakephp-queue-monitor/tree/2.next-cake5) | 2.next-cake5 | stable |
|  ^4.4   | [1.0.0](https://github.com/CakeDC/cakephp-queue-monitor/tree/1.next-cake4) | 1.next-cake4 | stable |

## Overview

The CakeDC Queue Monitor Plugin adds the ability to monitor jobs in queues that are handled by the
[CakePHP Queue Plugin](https://github.com/cakephp/queue). This plugin checks the duration of work of
individual Jobs and sends a notification when this time is exceeded by a configurable value.

## Requirements
* CakePHP 4.4
* PHP 8.1+

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer package is:
```
composer require cakedc/queue-monitor
```

## Configuration

Add QueueMonitorPlugin to your `Application::bootstrap`:
```php
use Cake\Http\BaseApplication;
use CakeDC\QueueMonitor\QueueMonitorPlugin;

class Application extends BaseApplication
{
    // ...

    public function bootstrap(): void
    {
        parent::bootstrap();

        $this->addPlugin(QueueMonitorPlugin::class);
    }

    // ...
}

```

Set up the QueueMonitor configuration in your `config/app_local.php`:
```php
// ...
    'QueueMonitor' => [
        // With this setting you can enable or disable the queue monitoring, the queue
        // monitoring is enabled by default
        'disable' => false,

        // mailer config, the default is `default` mailer, you can ommit
        // this setting if you use default value
        'mailerConfig' => 'myCustomMailer',

        // the default is 30 minutes, you can ommit this setting if you
        // use the default value
        'longJobInMinutes' => 45,

        // the default is 30 days, you can ommit this setting if you
        // its advised to set this value correctly after queue usage analysis to avoid
        // high space usage in db
        'purgeLogsOlderThanDays' => 10,

        // comma separated list of recipients of notification about long running queue jobs
        'notificationRecipients' => 'recipient1@yourdomain.com,recipient2@yourdomain.com,recipient3@yourdomain.com',
    ],
// ...
```

Run the required migrations
```shell
bin/cake migrations migrate -p CakeDC/QueueMonitor
```

For each queue configuration add `listener` setting
```php
// ...
    'Queue' => [
        'default' => [
            // ...
            'listener' => \CakeDC\QueueMonitor\Listener\QueueMonitorListener::class,
            // ...
        ]
    ],
// ...
```

## Notification command

To set up notifications when there are long running or possible stuck jobs please use command
```shell
bin/cake queue_monitor notify
```

This command will send notification emails to recipients specified in `QueueMonitor.notificationRecipients`. Best is
to use it as a cronjob

## Purge command

The logs table may grow overtime, to keep it slim you can use the purge command:
```shell
bin/cake queue_monitor purge
```

This command will purge logs older than value specified in `QueueMonitor.purgeLogsOlderThanDays`, the value is in
days, the default is 30 days. Best is to use it as a cronjob

## Important

Make sure your Job classes have a property value of maxAttempts because if it's missing, the log table can quickly
grow to gigantic size in the event of an uncaught exception in Job, Job is re-queued indefinitely in such a case.
