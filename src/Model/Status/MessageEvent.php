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

/**
 * MessageEvent
 */
enum MessageEvent: string
{
    case Seen = 'Processor.message.seen'; // 1 OK
    case Invalid = 'Processor.message.invalid'; // 2 ending
    case Start = 'Processor.message.start'; // 3 OK
    case Exception = 'Processor.message.exception'; // 4 ending
    case Success = 'Processor.message.success'; // 5 OK ending
    case Reject = 'Processor.message.reject'; // 6 OK ending
    case Failure = 'Processor.message.failure'; // 7 ending

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
     * Get Event enum from int value
     */
    public static function getEventFromInt(?int $eventInt): ?self
    {
        return match ($eventInt) {
            1 => self::Seen,
            2 => self::Invalid,
            3 => self::Start,
            4 => self::Exception,
            5 => self::Success,
            6 => self::Reject,
            7 => self::Failure,
            default => null,
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
