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
use Psr\Log\LogLevel;
use function Cake\I18n\__;

/**
 * Purge command.
 */
final class PurgeCommand extends Command
{
    use LogTrait;

    private const DEFAULT_PURGE_DAYS_OLD = 30;

    /**
     * Constructor
     */
    public function __construct(
        private readonly QueueMonitoringService $queueMonitoringService
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'queue_monitor purge';
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(__('Queue Monitoring log purger'));
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $purgeToDate = $this->queueMonitoringService->getPurgeToDate(
            (int)Configure::read(
                'QueueMonitor.purgeLogsOlderThanDays',
                self::DEFAULT_PURGE_DAYS_OLD
            )
        );
        $this->log(
            "Purging queue logs older than {$purgeToDate->toDateTimeString()} UTC",
            LogLevel::INFO
        );
        try {
            $rowCount = $this->queueMonitoringService->purgeLogs(self::DEFAULT_PURGE_DAYS_OLD);
            $this->log(
                "Purged $rowCount queue messages older than {$purgeToDate->toDateTimeString()} UTC",
                LogLevel::INFO
            );
        } catch (Exception $e) {
            $this->log("Failed puring `queue stuck` logs, reason: {$e->getMessage()}");

            return self::CODE_ERROR;
        }

        return self::CODE_SUCCESS;
    }
}
