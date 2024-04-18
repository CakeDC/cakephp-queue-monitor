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
namespace CakeDC\QueueMonitor\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Log\LogTrait;
use CakeDC\QueueMonitor\Service\QueueMonitoringService;
use Exception;

/**
 * QueueMonitoringNotify command.
 */
final class NotifyCommand extends Command
{
    use LogTrait;

    private const DEFAULT_LONG_JOB_IN_MINUTES = 30;

    /**
     * Constructor
     */
    public function __construct(
        private readonly QueueMonitoringService $queueMonitoringService
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'queue_monitor notify';
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(__('Queue Monitoring notifier'));
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        try {
            $this->queueMonitoringService->notifyAboutLongRunningJobs(
                (int)Configure::read(
                    'QueueMonitor.longJobInMinutes',
                    self::DEFAULT_LONG_JOB_IN_MINUTES
                )
            );
        } catch (Exception $e) {
            $this->log("Failed to send queue stuck notifications, reason: {$e->getMessage()}");

            return self::CODE_ERROR;
        }

        return self::CODE_SUCCESS;
    }
}
