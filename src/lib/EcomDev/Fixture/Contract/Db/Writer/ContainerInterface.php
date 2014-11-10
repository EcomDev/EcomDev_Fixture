<?php

use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;

/**
 * Container interface for a schedule processing
 * 
 */
interface EcomDev_Fixture_Contract_Db_Writer_ContainerInterface
    extends EcomDev_Fixture_Contract_Utility_ResetAwareInterface
{
    /**
     * Insert schedule operation
     * 
     * @var string
     */
    const TYPE_INSERT = 'insert';

    /**
     * Update schedule operation
     * 
     * @var string
     */
    const TYPE_UPDATE = 'update';

    /**
     * Delete schedule operation
     * 
     * @var string
     */
    const TYPE_DELETE = 'delete';

    /**
     * Serialized value key, that can be used during schedule operation
     *
     * @var string
     */
    const VALUE_SERIALIZED = 'serialized';

    /**
     * JSON value key, that can be used during schedule operation
     */
    const VALUE_JSON = 'json';

    /**
     * Primary schedule queue
     * 
     * @var int
     */
    const QUEUE_PRIMARY = 0;

    /**
     * Secondary schedule queue
     * 
     * @var int
     */
    const QUEUE_SECONDARY = 1;

    /**
     * Constructor with dependencies passed
     *
     * @param SchemaInterface $schema
     * @param ResolverInterface $resolver
     */
    public function __construct(SchemaInterface $schema, ResolverInterface $resolver);
    
    /**
     * Schedules an insert operation
     * 
     * @param string $table
     * @param array $data
     * @param int $queue
     * @return $this
     */
    public function scheduleInsert($table, $data, $queue = self::QUEUE_PRIMARY);

    /**
     * Schedules an update operation
     * 
     * @param $table
     * @param array $data
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleUpdate($table, $data, $condition = array(), $queue = self::QUEUE_PRIMARY);

    /**
     * Schedules a delete operation
     * 
     * @param string $table
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleDelete($table, $condition = array(), $queue = self::QUEUE_PRIMARY);

    /**
     * Returns resolved schedule items for table and type
     * 
     * @param int $queue
     * @param string $table
     * @param string $type
     * @return array
     */
    public function getSchedule($queue, $table, $type);

    /**
     * Checks if it is possible to use multiple inserts for an insert schedule
     * 
     * @param int $queue
     * @param string $table
     * @return bool
     */
    public function isInsertScheduleMultiple($queue, $table);

    /**
     * Returns schedule primary key map instance if it was passed before
     * into writer
     * 
     * @param int $queue
     * @param string $table
     * @param int $rowIndex
     * @return MapInterface|bool
     */
    public function getInsertSchedulePrimaryKeyMap($queue, $table, $rowIndex);
    
    /**
     * Should return tru of there is any schedule items left in queue
     * 
     * @param int $queue
     * @param string $table
     * @param string $type
     * @return bool
     */
    public function hasSchedule($queue, $table, $type);
    
    /**
     * Should return list of schedule resolve errors
     * 
     * Will return list of errors only if there are any items left in schedule
     *
     * @param int $queue
     * @param string $table
     * @param string $type
     * @return EcomDev_Fixture_Contract_Db_Writer_ErrorInterface[]
     */
    public function getScheduleResolveErrors($queue, $table, $type);

    /**
     * Returns list of tables that exists in current schedule
     * 
     * @param int $queue
     * @param string $type
     * @return string[]
     */
    public function getScheduleTables($queue, $type);

    /**
     * Resolves possible internal relations
     * 
     * @return $this
     */
    public function resolve();
}
