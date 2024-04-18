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
namespace CakeDC\QueueMonitor\Test\TestCase\Listener;

use Cake\Event\EventList;
use Cake\Log\Engine\ArrayLog;
use Cake\Queue\Job\Message;
use Cake\Queue\Queue\Processor;
use Cake\TestSuite\TestCase;
use CakeDC\QueueMonitor\Listener\QueueMonitorListener;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\NullMessage;
use Error;
use Exception;
use Interop\Queue\Processor as InteropProcessor;
use TypeError;

/**
 * QueueMonitorListenerTest
 */
class QueueMonitorListenerTest extends TestCase
{
    public static $lastProcessMessage;

    /**
     * @param string $method
     * @return void
     * @dataProvider dataProviderTestHandleException
     */
    public function testHandleException(string $method): void
    {
        $messageBody = [
            'class' => [static::class, $method],
            'data' => ['sample_data' => 'a value', 'key' => md5($method)],
        ];
        $connectionFactory = new NullConnectionFactory();
        $context = $connectionFactory->createContext();
        $queueMessage = new NullMessage(json_encode($messageBody));

        $events = new EventList();
        $logger = new ArrayLog();
        $processor = new Processor($logger);
        $processor->getEventManager()->setEventList($events);
        $processor->getEventManager()->on(new QueueMonitorListener());

        $result = $processor->process($queueMessage, $context);
        $this->assertEquals(InteropProcessor::REQUEUE, $result);
    }

    /**
     * Test process method
     *
     * @dataProvider dataProviderTestProcess
     */
    public function testProcess($jobMethod, $expected, $logMessage, $dispatchedEvent)
    {
        $messageBody = [
            'class' => [static::class, $jobMethod],
            'args' => [],
        ];
        $connectionFactory = new NullConnectionFactory();
        $context = $connectionFactory->createContext();
        $queueMessage = new NullMessage(json_encode($messageBody));
        $message = new Message($queueMessage, $context);

        $events = new EventList();
        $logger = new ArrayLog();
        $processor = new Processor($logger);
        $processor->getEventManager()->setEventList($events);
        $processor->getEventManager()->on(new QueueMonitorListener());

        $actual = $processor->process($queueMessage, $context);
        $this->assertSame($expected, $actual);

        $logs = $logger->read();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('debug', $logs[0]);
        $this->assertStringContainsString($logMessage, $logs[0]);

        $this->assertSame(3, $events->count());
        $this->assertSame('Processor.message.seen', $events[0]->getName());
        $this->assertEquals(['queueMessage' => $queueMessage], $events[0]->getData());

        // Events should contain a message with the same payload.
        $this->assertSame('Processor.message.start', $events[1]->getName());
        $data = $events[1]->getData();
        $this->assertArrayHasKey('message', $data);
        $this->assertSame($message->jsonSerialize(), $data['message']->jsonSerialize());

        $this->assertSame($dispatchedEvent, $events[2]->getName());
        $data = $events[2]->getData();
        $this->assertArrayHasKey('message', $data);
        $this->assertSame($message->jsonSerialize(), $data['message']->jsonSerialize());
    }

    /**
     * Job to be used in test testHandleException
     *
     * @throws \Exception
     */
    public static function processAndThrowException(Message $message)
    {
        throw new Exception('Something went wrong');
    }

    /**
     * Job to be used in test testHandleException
     *
     * @throws \TypeError
     */
    public static function processAndThrowTypeError(Message $message)
    {
        throw new TypeError('Type error');
    }

    /**
     * Job to be used in test testHandleException
     *
     * @throws \Error
     */
    public static function processAndThrowError(Message $message)
    {
        throw new Error('Error');
    }

    /**
     * Data provider used by testHandleException
     *
     * @return array[]
     */
    public function dataProviderTestHandleException(): array
    {
        return [
            ['processAndThrowException'],
            ['processAndThrowTypeError'],
            ['processAndThrowError'],
        ];
    }

    /**
     * Data provider for testProcess method
     *
     * @return array
     */
    public function dataProviderTestProcess(): array
    {
        return [
            'ack' => ['processReturnAck', InteropProcessor::ACK, 'Message processed successfully', 'Processor.message.success'],
            'null' => ['processReturnNull', InteropProcessor::ACK, 'Message processed successfully', 'Processor.message.success'],
            'reject' => ['processReturnReject', InteropProcessor::REJECT, 'Message processed with rejection', 'Processor.message.reject'],
            'requeue' => ['processReturnRequeue', InteropProcessor::REQUEUE, 'Message processed with failure, requeuing', 'Processor.message.failure'],
            'string' => ['processReturnString', InteropProcessor::REQUEUE, 'Message processed with failure, requeuing', 'Processor.message.failure'],
        ];
    }

    /**
     * Job to be used in test testProcess
     */
    public static function processReturnNull(Message $message)
    {
        static::$lastProcessMessage = $message;

        return null;
    }

    /**
     * Job to be used in test testProcess
     */
    public static function processReturnReject(Message $message)
    {
        static::$lastProcessMessage = $message;

        return InteropProcessor::REJECT;
    }

    /**
     * Job to be used in test testProcess
     */
    public static function processReturnAck(Message $message)
    {
        static::$lastProcessMessage = $message;

        return InteropProcessor::ACK;
    }

    /**
     * Job to be used in test testProcess
     */
    public static function processReturnRequeue(Message $message)
    {
        static::$lastProcessMessage = $message;

        return InteropProcessor::REQUEUE;
    }

    /**
     * Job to be used in test testProcess
     */
    public static function processReturnString(Message $message)
    {
        static::$lastProcessMessage = $message;

        return 'invalid value';
    }
}
