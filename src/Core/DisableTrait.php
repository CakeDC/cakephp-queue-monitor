<?php
declare(strict_types=1);

namespace CakeDC\QueueMonitor\Core;

use Cake\Core\Configure;

/**
 * Disable trait
 */
trait DisableTrait
{
    /**
     * Check if queue monitoring is disabled by configuration
     */
    protected function isDisabled(): bool
    {
        return (bool)Configure::read('QueueMonitor.disabled', false);
    }
}
