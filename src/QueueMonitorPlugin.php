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

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use CakeDC\QueueMonitor\Command\NotifyCommand;
use CakeDC\QueueMonitor\Command\PurgeCommand;
use CakeDC\QueueMonitor\Service\QueueMonitoringService;

/**
 * Plugin for QueueMonitor
 */
class QueueMonitorPlugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    protected $bootstrapEnabled = false;

    /**
     * @inheritDoc
     */
    protected $routesEnabled = false;

    /**
     * @inheritDoc
     */
    protected $middlewareEnabled = false;

    /**
     * @inheritDoc
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        return parent::console($commands)
            ->add('queue_monitor purge', PurgeCommand::class)
            ->add('queue_monitor notify', NotifyCommand::class);
    }

    /**
     * @inheritDoc
     */
    public function services(ContainerInterface $container): void
    {
        $container->add(QueueMonitoringService::class);
        $container
            ->add(PurgeCommand::class)
            ->addArguments([
                QueueMonitoringService::class,
            ]);
        $container
            ->add(NotifyCommand::class)
            ->addArguments([
                QueueMonitoringService::class,
            ]);
    }
}
