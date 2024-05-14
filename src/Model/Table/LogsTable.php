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
namespace CakeDC\QueueMonitor\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use CakeDC\QueueMonitor\Model\Status\MessageEvent;

/**
 * Logs Model
 *
 * @method \CakeDC\QueueMonitor\Model\Entity\Log newEmptyEntity()
 * @method \CakeDC\QueueMonitor\Model\Entity\Log newEntity(array $data, array $options = [])
 * @method \CakeDC\QueueMonitor\Model\Entity\Log[] newEntities(array $data, array $options = [])
 * @method \CakeDC\QueueMonitor\Model\Entity\Log findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \CakeDC\QueueMonitor\Model\Entity\Log patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \CakeDC\QueueMonitor\Model\Entity\Log[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \CakeDC\QueueMonitor\Model\Entity\Log|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \CakeDC\QueueMonitor\Model\Entity\Log saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 */
class LogsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('queue_monitoring_logs');
        $this->setDisplayField('event');
        $this->setPrimaryKey('id');
    }

    /**
     * Find entity with last event
     *
     * @uses \CakeDC\QueueMonitor\Model\Table\LogsTable::findLastEvent()
     */
    public function findWithLastEvent(SelectQuery $selectQuery): SelectQuery
    {
        return $selectQuery
            ->find('lastEvent')
            ->select($this);
    }

    /**
     * Find last event
     */
    public function findLastEvent(SelectQuery $selectQuery): SelectQuery
    {
        return $selectQuery
            ->select([
                'last_event' => $selectQuery->func()->max($this->aliasField('event'), ['integer']),
                'last_created' => $selectQuery->func()->max($this->aliasField('created'), ['datetime']),
                'message_timestamp',
            ])
            ->groupBy($this->aliasField('message_id'));
    }

    /**
     * Find stuck jobs
     *
     * @throws \Exception
     * @uses \CakeDC\QueueMonitor\Model\Table\LogsTable::findLastEvent()
     */
    public function findStuckJobs(SelectQuery $selectQuery, DateTime $olderThan): SelectQuery
    {
        return $selectQuery
            ->find('lastEvent')
            ->having(fn (QueryExpression $queryExpression): QueryExpression => $queryExpression
                ->in('last_event', MessageEvent::getNotEndingEventsAsInts())
                ->lte('last_created', $olderThan->toDateTimeString()));
    }
}
