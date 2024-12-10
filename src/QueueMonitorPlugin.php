<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\QueueMonitor;

use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use CakeDC\QueueMonitor\Command\NotifyCommand;
use CakeDC\QueueMonitor\Command\PurgeLogsCommand;
use CakeDC\QueueMonitor\Command\PurgeQueueCommand;
use CakeDC\QueueMonitor\Service\EnqueueClientService;
use CakeDC\QueueMonitor\Service\QueueMonitoringService;

/**
 * Plugin for QueueMonitor
 */
class QueueMonitorPlugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    protected bool $bootstrapEnabled = false;

    /**
     * @inheritDoc
     */
    protected bool $routesEnabled = false;

    /**
     * @inheritDoc
     */
    protected bool $middlewareEnabled = false;

    /**
     * @inheritDoc
     */
    public function services(ContainerInterface $container): void
    {
        $container->add(QueueMonitoringService::class);
        $container->addShared(EnqueueClientService::class);

        $container
            ->add(PurgeLogsCommand::class)
            ->addArguments([
                QueueMonitoringService::class,
            ]);
        $container
            ->add(PurgeQueueCommand::class)
            ->addArguments([
                EnqueueClientService::class,
            ]);
        $container
            ->add(NotifyCommand::class)
            ->addArguments([
                QueueMonitoringService::class,
            ]);
    }
}
