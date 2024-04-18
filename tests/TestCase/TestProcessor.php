<?php
declare(strict_types=1);

namespace CakeDC\QueueMonitor\Test\TestCase;

use Cake\Queue\Job\Message;
use Error;
use Exception;
use Interop\Queue\Processor as InteropProcessor;
use TypeError;

class TestProcessor
{
    public static Message $lastProcessMessage;

    /**
     * Job to be used in test testProcessMessageCallableIsString
     *
     * @throws Exception
     */
    public static function processAndThrowException(Message $message)
    {
        throw new Exception('Something went wrong');
    }

    /**
     * Job to be used in test testProcessMessageCallableIsString
     */
    public static function processReturnAck(Message $message)
    {
        static::$lastProcessMessage = $message;

        return InteropProcessor::ACK;
    }

    /**
     * Job to be used in test testProcessMessageCallableIsString
     */
    public static function processReturnNull(Message $message)
    {
        static::$lastProcessMessage = $message;

        return null;
    }

    /**
     * Job to be used in test testProcessMessageCallableIsString
     */
    public static function processReturnReject(Message $message)
    {
        static::$lastProcessMessage = $message;

        return InteropProcessor::REJECT;
    }

    /**
     * Job to be used in test testProcessMessageCallableIsString
     */
    public static function processReturnRequeue(Message $message)
    {
        static::$lastProcessMessage = $message;

        return InteropProcessor::REQUEUE;
    }

    /**
     * Job to be used in test testProcessMessageCallableIsString
     */
    public static function processReturnString(Message $message)
    {
        static::$lastProcessMessage = $message;

        return 'invalid value';
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
}
