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

use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Validation\Validation;
use CakeDC\QueueMonitor\Exception\QueueMonitorException;
use CakeDC\QueueMonitor\Model\Table\LogsTable;

/**
 * QueueMonitoringService
 */
final class QueueMonitoringService
{
    use LocatorAwareTrait;
    use LogTrait;

    private LogsTable|Table $QueueMonitoringLogsTable;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->QueueMonitoringLogsTable = $this->fetchTable(LogsTable::class);
    }

    /**
     * Get purge `to date` value
     */
    public function getPurgeToDate(int $daysOld): DateTime
    {
        return DateTime::now('UTC')
            ->subDays($daysOld)
            ->endOfDay();
    }

    /**
     * Purge old logs
     */
    public function purgeLogs(int $daysOld): int
    {
        return $this->QueueMonitoringLogsTable->deleteAll(
            fn (QueryExpression $queryExpression): QueryExpression => $queryExpression->lte(
                $this->QueueMonitoringLogsTable->aliasField('message_timestamp'),
                $this->getPurgeToDate($daysOld),
                TableSchemaInterface::TYPE_DATETIME
            )
        );
    }

    /**
     * get the list of jobs that are have last event older than 30 minutes and event type is not finished
     * in any way (seen, start)
     *
     * @throws \Exception
     */
    public function notifyAboutLongRunningJobs(int $longJobsInMinutes): void
    {
        $olderThan = DateTime::now('UTC')->subMinutes($longJobsInMinutes);

        /**
         * @uses \CakeDC\QueueMonitor\Model\Table\LogsTable::findStuckJobs()
         */
        $runningJobs = $this->QueueMonitoringLogsTable
            ->find(type: 'stuckJobs', olderThan: $olderThan)
            ->all();

        if ($runningJobs->count()) {
            $notifyEmails = Configure::read('QueueMonitor.notificationRecipients');
            if (!$notifyEmails) {
                throw new QueueMonitorException(
                    'Missing `QueueMonitor.notificationRecipients` configuration'
                );
            }
            $notifyEmails = explode(',', $notifyEmails);
            $mailerConfig = Configure::read('QueueMonitor.mailerConfig', 'default');
            $mailer = new Mailer($mailerConfig);
            foreach ($notifyEmails as $notifyEmail) {
                if (!Validation::email($notifyEmail)) {
                    throw new QueueMonitorException(
                        'Invalid notification email in `QueueMonitor.notificationRecipients`'
                    );
                }
                $mailer->addTo(trim($notifyEmail));
            }
            $mailer->setSubject('Emergency. There are jobs stuck in queue.')
                ->deliver('This is automated message about queue job stuck in queue engine.' .
                    " \n\nThere are {$runningJobs->count()} stuck in queue for the last $longJobsInMinutes " .
                    'minutes and more.');
        }
    }
}
