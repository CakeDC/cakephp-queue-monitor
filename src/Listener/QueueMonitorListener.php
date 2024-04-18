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
namespace CakeDC\QueueMonitor\Listener;

use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Queue\Job\Message;
use Cake\Utility\Hash;
use CakeDC\QueueMonitor\Exception\QueueMonitorException;
use CakeDC\QueueMonitor\Model\Status\MessageEvent;
use CakeDC\QueueMonitor\Model\Table\LogsTable;
use Exception;
use Interop\Queue\Message as QueueMessage;
use Throwable;

/**
 * QueueMonitorListener
 *
 * Records all information about the jobs into queue monitoring job table
 */
final class QueueMonitorListener implements EventListenerInterface
{
    use LocatorAwareTrait;
    use LogTrait;

    private LogsTable|Table $QueueMonitoringLogs;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->QueueMonitoringLogs = $this->fetchTable(LogsTable::class);
    }

    /**
     * Implemented events
     */
    public function implementedEvents(): array
    {
        /**
         * @uses \CakeDC\QueueMonitor\Listener\QueueMonitorListener::handleMessageEvent()
         * @uses \CakeDC\QueueMonitor\Listener\QueueMonitorListener::handleException()
         * @uses \CakeDC\QueueMonitor\Listener\QueueMonitorListener::handleSeen()
         */
        return [
            'Processor.message.exception' => 'handleException',
            'Processor.message.invalid' => 'handleMessageEvent',
            'Processor.message.reject' => 'handleMessageEvent',
            'Processor.message.success' => 'handleMessageEvent',
            'Processor.message.failure' => 'handleMessageEvent',
            'Processor.message.seen' => 'handleSeen',
            'Processor.message.start' => 'handleMessageEvent',
        ];
    }

    /**
     * Handle event `Processor.message.exception`
     */
    public function handleException(EventInterface $event, ?Message $message, ?Throwable $exception = null): void
    {
        try {
            $message = $this->validateQueueMessage($message);

            if (!$exception) {
                throw new QueueMonitorException(
                    'Queue Exception is null, ensure that the queue job is set up correctly'
                );
            }

            $this->storeEvent(
                $event->getName(),
                implode('::', $message->getTarget()),
                $message->getOriginalMessage(),
                $exception
            );
        } catch (Exception $e) {
            $this->log("Unable to handle queue monitoring exception message event, reason: {$e->getMessage()}");
        }
    }

    /**
     * Handle events
     *  `Processor.message.invalid`
     *  `Processor.message.reject`
     *  `Processor.message.success`
     *  `Processor.message.failure`
     *  `Processor.message.start`
     */
    public function handleMessageEvent(EventInterface $event, ?Message $message): void
    {
        try {
            $message = $this->validateQueueMessage($message);

            $this->storeEvent(
                $event->getName(),
                implode('::', $message->getTarget()),
                $message->getOriginalMessage()
            );
        } catch (Exception $e) {
            $this->log('Unable to handle queue monitoring message event ' .
                "`{$event->getName()}`, reason: {$e->getMessage()}");
        }
    }

    /**
     * Handle event `Processor.message.seen`
     */
    public function handleSeen(EventInterface $event, ?QueueMessage $queueMessage): void
    {
        try {
            $queueMessage = $this->validateInteropQueueMessage($queueMessage);
            $messageBody = json_decode($queueMessage->getBody(), true);
            $target = is_array($messageBody) ?
                implode('::', Hash::get($messageBody, 'class')) :
                '';

            $this->storeEvent(
                $event->getName(),
                $target,
                $queueMessage
            );
        } catch (Exception $e) {
            $this->log('Unable to handle queue monitoring message event ' .
                "`{$event->getName()}`, reason: {$e->getMessage()}");
        }
    }

    /**
     * @throws \Exception
     */
    private function storeEvent(
        string $eventName,
        string $target,
        QueueMessage $queueMessage,
        ?Throwable $exception = null
    ): void {
        if (is_null($queueMessage->getMessageId())) {
            throw new QueueMonitorException('Missing message id in queue message');
        }
        if (is_null($queueMessage->getTimestamp())) {
            throw new QueueMonitorException('Missing timestamp in queue message');
        }

        /** @var \CakeDC\QueueMonitor\Model\Entity\Log $queueMonitoringLog */
        $queueMonitoringLog = $this->QueueMonitoringLogs->newEmptyEntity();

        $queueMonitoringLog->message_id = (string)$queueMessage->getMessageId();
        $queueMonitoringLog->message_timestamp = DateTime::createFromTimestamp(
            (int)$queueMessage->getTimestamp(),
            'UTC'
        );
        $queueMonitoringLog->event = MessageEvent::from($eventName)->getEventAsInt();
        $queueMonitoringLog->job = $target;
        $queueMonitoringLog->exception = $exception ? get_class($exception) : null;
        $queueMonitoringLog->content = (string)json_encode([
            'body' => json_decode($queueMessage->getBody(), true),
            'headers' => $queueMessage->getHeaders(),
            'properties' => $queueMessage->getProperties(),
        ]);

        $this->QueueMonitoringLogs->saveOrFail($queueMonitoringLog);
    }

    /**
     * Validate queue message
     *
     * @throws \CakeDC\QueueMonitor\Exception\QueueMonitorException
     */
    public function validateQueueMessage(?Message $message): Message
    {
        if (!($message instanceof Message) || !is_string($message->getOriginalMessage()->getMessageId())) {
            throw new QueueMonitorException(
                'Message is not an instance of \Cake\Queue\Job\Message, ' .
                'ensure that the queue job is set up correctly'
            );
        }

        return $message;
    }

    /**
     * Validate Interop Queue Message
     *
     * @throws \CakeDC\QueueMonitor\Exception\QueueMonitorException
     */
    public function validateInteropQueueMessage(?QueueMessage $queueMessage): QueueMessage
    {
        if (!($queueMessage instanceof QueueMessage)) {
            throw new QueueMonitorException(
                'Interop QueueMessage is not an instance of \Interop\Queue\Message, ' .
                'ensure that the queue job is set up correctly'
            );
        }

        return $queueMessage;
    }
}
