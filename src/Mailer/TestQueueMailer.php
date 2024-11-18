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
namespace CakeDC\QueueMonitor\Mailer;

use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;
use Cake\Queue\Mailer\QueueTrait;

/**
 * Test Queue Mailer
 */
class TestQueueMailer extends Mailer
{
    use QueueTrait;

    public const SEND_TEST_QUEUE = 'testQueue';

    /**
     * Mailer's name.
     *
     * @var string
     */
    public static $name = 'TestQueue';

    /**
     * Send test email
     */
    public function testQueue(string $emailAddress, ?string $queueConfig = 'default'): void
    {
        $this
            ->setProfile(Configure::read('QueueMonitor.mailerConfig', 'default'))
            ->setTo($emailAddress)
            ->setSubject(__('Test enqueue from queue `{0}`', $queueConfig))
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->viewBuilder()
                ->disableAutoLayout()
                ->setTemplate('QueueMonitor.test_enqueue');
    }
}
