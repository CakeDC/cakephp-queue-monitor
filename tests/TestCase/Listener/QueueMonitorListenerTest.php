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
use CakeDC\QueueMonitor\Test\TestCase\TestProcessor;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\NullMessage;
use Interop\Queue\Processor as InteropProcessor;

/**
 * QueueMonitorListenerTest
 */
class QueueMonitorListenerTest extends TestCase
{
    public static $lastProcessMessage;

    /**
     * Data provider for testProcess method
     *
     * @return array
     */
    public static function dataProviderTestProcess(): array
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
     * Data provider used by testHandleException
     *
     * @return array[]
     */
    public static function dataProviderTestHandleException(): array
    {
        return [
            ['processAndThrowException'],
            ['processAndThrowTypeError'],
            ['processAndThrowError'],
        ];
    }

    /**
     * Test process method
     *
     * @param string $jobMethod The method name to run
     * @param string $expected The expected process result.
     * @param string $logMessage The log message based on process result.
     * @param string $dispacthedEvent The dispatched event based on process result.
     * @dataProvider dataProviderTestProcess
     * @return void
     */
    public function testProcess($jobMethod, $expected, $logMessage, $dispatchedEvent)
    {
        $messageBody = [
            'class' => [TestProcessor::class, $jobMethod],
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
     * When processMessage() throws an exception, test that
     * requeue will return.
     *
     * @return void
     * @dataProvider dataProviderTestHandleException
     */
    public function testProcessWillRequeueOnException(string $method)
    {
        $messageBody = [
            'class' => [TestProcessor::class, $method],
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
     * Test processMessage method.
     *
     * @return void
     */
    public function testProcessMessage()
    {
        $messageBody = [
            'class' => [TestProcessor::class, 'processReturnAck'],
            'args' => [],
        ];
        $connectionFactory = new NullConnectionFactory();
        $context = $connectionFactory->createContext();
        $queueMessage = new NullMessage(json_encode($messageBody));
        $message = new Message($queueMessage, $context);
        $processor = new Processor();
        $processor->getEventManager()->on(new QueueMonitorListener());

        $result = $processor->processMessage($message);
        $this->assertSame(InteropProcessor::ACK, $result);
        $this->assertNotEmpty(TestProcessor::$lastProcessMessage);
    }
}
