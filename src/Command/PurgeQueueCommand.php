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
use CakeDC\QueueMonitor\Core\DisableTrait;
use CakeDC\QueueMonitor\Exception\QueueMonitorException;
use CakeDC\QueueMonitor\Service\EnqueueClientService;
use Psr\Log\LogLevel;

/**
 * Purge command.
 */
final class PurgeQueueCommand extends Command
{
    use DisableTrait;
    use LogTrait;

    /**
     * Constructor
     */
    public function __construct(
        private readonly EnqueueClientService $enqueueClientService,
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'queue-monitor purge-queue';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return __('Purge messages from specified queue');
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(self::getDescription())
            ->addArgument('queue-config', [
                'help' => __('Queue configuration key'),
            ]);
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        if ($this->isDisabled()) {
            $this->log(
                'Logs were not purged because Queue Monitor is disabled.',
                LogLevel::WARNING
            );

            return self::CODE_SUCCESS;
        }
        $queueConfig = $args->getArgument('queue-config');

        if (!$this->validateQueueConfig($queueConfig)) {
            $io->error(__('Queue configuration key is invalid'));
            $configuredQueues = $this->getConfiguredQueues();
            if ($configuredQueues) {
                $io->error(__('Valid configuration keys are: {0}', implode(', ', $configuredQueues)));
            } else {
                $io->error(__('There are no queue configurations'));
            }

            return self::CODE_ERROR;
        }

        try {
            $this->enqueueClientService->purgeQueue($queueConfig);
            $io->success(__('Queue purged successfully'));

            return self::CODE_SUCCESS;
        } catch (QueueMonitorException $e) {
            $io->error(__('Unable to purge queue, reason: {0}', $e->getMessage()));

            return self::CODE_ERROR;
        }
    }

    /**
     * Validate queue config
     */
    private function validateQueueConfig(?string $queueConfig): bool
    {
        if (empty($queueConfig)) {
            return false;
        }

        $validQueueConfigs = $this->getConfiguredQueues();

        if (!in_array($queueConfig, $validQueueConfigs, true)) {
            return false;
        }

        return true;
    }

    /**
     * Get configured queues
     */
    private function getConfiguredQueues(): array
    {
        return array_keys(Configure::read('Queue', []));
    }
}
