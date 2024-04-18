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
namespace CakeDC\QueueMonitor\Model\Status;

use function Cake\Collection\collection;

/**
 * MessageEvent
 */
enum MessageEvent: string
{
    case Seen = 'Processor.message.seen';
    case Invalid = 'Processor.message.invalid';
    case Start = 'Processor.message.start';
    case Exception = 'Processor.message.exception';
    case Success = 'Processor.message.success';
    case Reject = 'Processor.message.reject';
    case Failure = 'Processor.message.failure';

    /**
     * Get event as int
     */
    public function getEventAsInt(): int
    {
        return match ($this) {
            self::Seen => 1,
            self::Invalid => 2,
            self::Start => 3,
            self::Exception => 4,
            self::Success => 5,
            self::Reject => 6,
            self::Failure => 7,
        };
    }

    /**
     * Get as options
     */
    public static function getOptions(): array
    {
        return collection(self::cases())
            ->combine(
                fn (MessageEvent $messageEvent) => $messageEvent->getEventAsInt(),
                fn (MessageEvent $messageEvent) => $messageEvent->name
            )
            ->toArray();
    }

    /**
     * Get events that indicates that job ended
     */
    public static function getNotEndingEvents(): array
    {
        return [
            self::Seen,
            self::Start,
        ];
    }

    /**
     * Get events that indicates that job ended (int array)
     */
    public static function getNotEndingEventsAsInts(): array
    {
        return collection(self::getNotEndingEvents())
            ->map(fn (self $messageEvent): int => $messageEvent->getEventAsInt())
            ->toList();
    }
}
