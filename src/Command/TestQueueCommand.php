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
use Cake\Mailer\MailerAwareTrait;
use Cake\Validation\Validation;
use CakeDC\QueueMonitor\Core\DisableTrait;
use Psr\Log\LogLevel;
use function Cake\Collection\collection;
use function Cake\I18n\__;

/**
 * Test Queue Command
 */
final class TestQueueCommand extends Command
{
    use DisableTrait;
    use MailerAwareTrait;

    private const ARGUMENT_EMAIL = 'email';

    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'queue-monitor test-queue';
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return __('Enqueue test email');
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(self::getDescription())
            ->addArgument($this::ARGUMENT_EMAIL, [
                'help' => __('Email to send to'),
                'required' => true,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        if ($this->isDisabled()) {
            $this->log(
                'Test Enqueue was not performed because Queue Monitor is disabled.',
                LogLevel::WARNING
            );

            return self::CODE_SUCCESS;
        }

        $email = $args->getArgument(self::ARGUMENT_EMAIL);
        if (!Validation::email($email)) {
            $io->error(__('Invalid email'));

            return $this::CODE_ERROR;
        }

        collection(Configure::read('Queue', []))
            ->each(function (
                array $queueConfig,
                string $queueConfigKey
            ) use (
                $email,
                $io
            ): void {
                /** @var \CakeDC\QueueMonitor\Mailer\TestQueueMailer $mailer */
                $mailer = $this->getMailer('CakeDC/QueueMonitor.TestQueue');
                /** @uses \CakeDC\QueueMonitor\Mailer\TestQueueMailer::testQueue() */
                $mailer->push(
                    action: $mailer::SEND_TEST_QUEUE,
                    args: [
                        $email,
                        $queueConfigKey,
                    ],
                    options: [
                        'config' => $queueConfigKey,
                    ]
                );
                $io->info(__(
                    'Queued test email `{0}` in queue `{1}`',
                    $email,
                    $queueConfigKey
                ));
            });

        return $this::CODE_SUCCESS;
    }
}
