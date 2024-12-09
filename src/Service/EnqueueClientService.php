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
namespace CakeDC\QueueMonitor\Service;

use Cake\Queue\QueueManager;
use CakeDC\QueueMonitor\Exception\QueueMonitorException;
use Enqueue\Client\Config;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;

/**
 * Enqueue client service
 */
final class EnqueueClientService
{
    /**
     * Purge all messages from specified queue
     *
     * @throws \CakeDC\QueueMonitor\Exception\QueueMonitorException
     */
    public function purgeQueue(string $queueConfig): void
    {
        try {
            $simpleClient = QueueManager::engine($queueConfig);
            $context = $simpleClient->getDriver()->getContext();
            $queueName = $this->getEnqueueInternalQueueName($simpleClient->getDriver()->getConfig());
            $queue = $context->createQueue($queueName);
            $context->purgeQueue($queue);
        } catch (PurgeQueueNotSupportedException $e) {
            throw new QueueMonitorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get enqueue internal queue name
     */
    private function getEnqueueInternalQueueName(Config $enqueueClientConfig): string
    {
        return implode('.', [
            $enqueueClientConfig->getPrefix(),
            $enqueueClientConfig->getApp(),
            $enqueueClientConfig->getDefaultQueue(),
        ]);
    }
}
