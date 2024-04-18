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
namespace CakeDC\QueueMonitor\Model\Entity;

use Cake\ORM\Entity;

/**
 * QueueMonitorLog Entity
 *
 * @property string $id
 * @property \Cake\I18n\FrozenTime $created
 * @property string $message_id
 * @property \Cake\I18n\FrozenTime $message_timestamp
 * @property int $event
 * @property string $job
 * @property string|null $exception
 * @property int|null $last_event
 * @property string $content
 * @property \Cake\I18n\FrozenTime|null $last_created
 */
class Log extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'created' => true,
        'message_id' => true,
        'message_timestamp' => true,
        'event' => true,
        'job' => true,
        'exception' => true,
        'content' => true,
    ];
}
