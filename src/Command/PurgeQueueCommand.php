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
use function Cake\Collection\collection;
use function Cake\I18n\__;

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
            ])
            ->addOption('all', [
                'help' => __('All messages will be purged'),
                'short' => 'a',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('yes', [
                'short' => 'y',
                'boolean' => true,
                'default' => false,
                'help' => __('Yes - skip confirmation prompt'),
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

        if ($args->getOption('all')) {
            $this->checkConfirmation(
                __('Are you sure you want to purge messages from all queues?'),
                $args,
                $io
            );

            collection($this->getConfiguredQueues())->each(function (string $queueConfig) use ($io): void {
                try {
                    $this->enqueueClientService->purgeQueue($queueConfig);
                    $io->success(__('Queue `{0}` purged successfully', $queueConfig));
                } catch (QueueMonitorException $e) {
                    $io->error(__('Unable to purge queue `{0}`, reason: {1}', $queueConfig, $e->getMessage()));
                }
            });
        } else {
            $queueConfig = $args->getArgument('queue-config');

            if (!$this->validateQueueConfig($queueConfig)) {
                $io->error(__('Queue configuration key is invalid'));
                $configuredQueues = $this->getConfiguredQueues();
                if ($configuredQueues) {
                    $io->error(__('Valid configuration keys are: {0}', implode(', ', $configuredQueues)));
                } else {
                    $io->error(__('There are no queue configurations'));
                }
                $this->displayHelp($this->getOptionParser(), $args, $io);

                return self::CODE_ERROR;
            }

            $this->checkConfirmation(
                __('Are you sure you want to purge messages from specified queue?'),
                $args,
                $io
            );

            try {
                $this->enqueueClientService->purgeQueue($queueConfig);
                $io->success(__('Queue `{0}` purged successfully', $queueConfig));

                return self::CODE_SUCCESS;
            } catch (QueueMonitorException $e) {
                $io->error(__('Unable to purge queue `{0}`, reason: {1}', $queueConfig, $e->getMessage()));

                return self::CODE_ERROR;
            }
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

    /**
     * Check confirmation
     */
    private function checkConfirmation(string $prompt, Arguments $args, ConsoleIo $io): void
    {
        if (!$args->getOption('yes')) {
            $confirmation = $io->askChoice(
                $prompt,
                [
                    __('yes'),
                    __('no'),
                ],
                __('no')
            );

            if ($confirmation === __('no')) {
                $io->abort(__('Aborting'));
            }
        }
    }
}
