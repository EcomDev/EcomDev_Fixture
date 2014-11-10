<?php

use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_Schema_ColumnInterface as ColumnInterface;
use EcomDev_Fixture_Contract_Db_Schema_TableInterface as TableInterface;

/**
 * Writer container class
 * 
 * 
 */
class EcomDev_Fixture_Db_Writer_Container 
    implements EcomDev_Fixture_Contract_Db_Writer_ContainerInterface,
               EcomDev_Fixture_Contract_Utility_NotifierInterface
{
    /**
     * Schema instance
     * 
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Resolver instance
     * 
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * List of known maps to writer container
     * 
     * Contains map of spl_object_hash to actual object
     * 
     * @var MapInterface[]
     */
    protected $knownMaps = array();

    /**
     * Array containing different schedules in different queues
     *  
     * Data format is:
     * queue/type => array(
     *   table => array(
     *      rowIndex => rowData
     *   )
     * )
     * 
     * @var array[]
     */
    protected $schedule = array();

    /**
     * This property contains maps that were found during schedule creation
     * 
     * Data format is:
     * spl_object_hash($object) => array(
     *    queue/type/table/rowIndex/column => true
     * )
     * 
     * @var array
     */
    protected $scheduleColumnMap = array();

    /**
     * This property contains maps that were found during schedule creation
     *
     * Data format is:
     * spl_object_hash($object) => array(
     *    queue/type/table/rowIndex/condition => index or true
     * )
     *
     * @var array
     */
    protected $scheduleConditionMap = array();

    /**
     * This property contains information about resolved rows
     * 
     * Data format is:
     * queue/type/table => array(
     *    rowIndex => rowIndex
     * )
     * 
     * @var array[]
     */
    protected $resolvedSchedule = array();

    /**
     * Contains list of maps that were resolved during schedule 
     * or notification about resolution
     * 
     * Data format is:
     * spl_object_hash => spl_object_hash 
     * 
     * @var string[]
     */
    protected $resolvedMaps = array();

    /**
     * Contains index of unresolved primary key
     * 
     * Data format is:
     * queue/type/table => array(
     *    spl_object_hash => $rowIndex 
     * )
     *
     * @var array[]
     */
    protected $unresolvedSchedulePrimaryKey = array();
    
    /**
     * Contains index of unresolved schedule columns
     * Data format is:
     * queue/type/table => array(
     *    rowIndex => array(
     *      columnName => spl_object_hash
     *    )
     * )
     * 
     * @var array[]
     */
    protected $unresolvedScheduleColumn = array();

    /**
     * Contains index of unresolved schedule columns
     * Data format is:
     * queue/type/table => array(
     *    rowIndex => array(
     *      condition => array(
     *         spl_object_hash => index or true
     *      )
     *    )
     * )
     *
     * @var array[]
     */
    protected $unresolvedScheduleCondition = array();
    
    /**
     * Constructor with dependencies passed
     *
     * @param SchemaInterface $schema
     * @param ResolverInterface $resolver
     */
    public function __construct(SchemaInterface $schema, ResolverInterface $resolver)
    {
        $this->schema = $schema;
        $this->resolver = $resolver;
    }

    /**
     * Schedules an insert operation
     * 
     * Throws exception in case of invalid data supplied for data
     *
     * @param string $table
     * @param array $data
     * @param int $queue
     * @throws Exception
     * @return $this
     */
    public function scheduleInsert($table, $data, $queue = self::QUEUE_PRIMARY)
    {
        $table = $this->schema->getTableInfo($table);
        
        $primaryKey = $table->getPrimaryKeyColumn();
        if ($primaryKey instanceof ColumnInterface) {
            $currentPrimaryKeyValue = null;
            
            if (isset($data[$primaryKey->getName()])) {
                $currentPrimaryKeyValue = $data[$primaryKey->getName()];
            }
            
            if (!$currentPrimaryKeyValue instanceof MapInterface) {
                $map = $this->registerRowMap($table->getName(), $data);
                if ($map instanceof MapInterface) {
                    $map->setValue($currentPrimaryKeyValue);
                    $data[$primaryKey->getName()] = $map;
                }
            }
        }
        
        
        // Callback that initializes data for row
        $rowInitializer = function ($rowIndex, $data, $key, $tableName) use ($table) {
            $tableKey = $this->implodeKey($key, $tableName);
            $result = array();
            foreach ($table->getColumns() as $column) {
                $value = null;
                if ($column->isPrimary()) {
                    if (!isset($data[$column->getName()]) && !$column->isIdentity()) {
                        throw new InvalidArgumentException(
                            sprintf(
                                'The primary key "%s" for "%s" is required, since it is not autoincrement based.',
                                $column->getName(),
                                $table->getName()
                            )
                        );
                    } elseif (isset($data[$column->getName()])) {
                        $value = $data[$column->getName()];
                    }
                } elseif (isset($data[$column->getName()])) {
                    $value = $this->prepareColumnValue($table, $column, $data[$column->getName()]);
                } else {
                    $value = $this->prepareColumnValue($table, $column, null);
                }

                $result[$column->getName()] = $value;

                if ($value instanceof MapInterface) {
                    $this->registerColumnMap($value, $column, $tableKey, $rowIndex);
                }
            }
    
            return $result;
        };
        
        $this->initializeScheduleRow(
            $this->implodeKey($queue, self::TYPE_INSERT), $table->getName(), 
            $data, 
            $rowInitializer
        );
        
        return $this;
    }

    /**
     * Initializes schedule row and wraps everything into exception handler,
     * So any exception during mapping will be  
     * 
     * @param $key
     * @param $tableName
     * @param $data
     * @param callable $callable
     * @return $this
     * @throws Exception
     */
    protected function initializeScheduleRow($key, $tableName, $data, Closure $callable)
    {
        // Initialize row for insertion
        $this->schedule[$key][$tableName][] = array();
        end($this->schedule[$key][$tableName]);
        $rowIndex = key($this->schedule[$key][$tableName]);
        $tableKey = $this->implodeKey($key, $tableName);

        try {
            $this->schedule[$key][$tableName][$rowIndex] = $callable($rowIndex, $data, $key, $tableName);
            $this->resolveSchedule($tableKey, $rowIndex);
        } catch (Exception $e) {
            unset($this->schedule[$key][$tableName][$rowIndex]);
            throw $e;
        }
        
        return $this;
    }

    /**
     * Registers row map
     * 
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function registerRowMap($table, $data)
    {
        if ($this->resolver->canMapRow($table)) {
            return $this->resolver->mapRow($table, $data);
        }
        
        return false;
    }
    
    /**
     * Schedules an update operation
     *
     * @param $table
     * @param array $data
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleUpdate($table, $data, $condition = array(), $queue = self::QUEUE_PRIMARY)
    {
        $table = $this->schema->getTableInfo($table);

        $rowInitializer = function ($rowIndex, $data, $key, $tableName) use ($table, $condition) {
            $tableKey = $this->implodeKey($key, $tableName);
            $result = array(
                'data' => array(),
                'condition' => array()
            );
            
            $columns = $table->getColumns();
            foreach ($columns as $column) {
                $columnName = $column->getName();
                if (array_key_exists($columnName, $condition)) {
                    if (is_array($condition[$columnName])) {
                        $conditionKey = $columnName . ' IN(?)';
                    } elseif ($condition[$columnName] === null) {
                        $conditionKey = $columnName . ' IS NULL';
                    } else {
                        $conditionKey = $columnName . ' = ?';
                    }
                    
                    $condition[$conditionKey] = $condition[$columnName];
                    unset($condition[$columnName]);
                }
                
                if (!isset($data[$column->getName()])) {
                    continue;
                }

                $value = $this->prepareColumnValue($table, $column, $data[$column->getName()]);
                $result['data'][$column->getName()] = $value;
                if ($value instanceof MapInterface) {
                    $this->registerColumnMap($value, $column, $tableKey, $rowIndex);
                }
            }
            
            $result['condition'] = $condition;
            
            foreach ($condition as $conditionKey => $value) {
                if (is_array($value)) {
                    foreach ($value as $index => $item) {
                        if ($item instanceof MapInterface) {
                            $this->registerConditionMap($item, $conditionKey, $tableKey, $rowIndex, $index);
                        }
                    }
                } elseif ($value instanceof MapInterface) {
                    $this->registerConditionMap($value, $conditionKey, $tableKey, $rowIndex);
                }
            }

            return $result;
        };
        
        $this->initializeScheduleRow(
            $this->implodeKey($queue, self::TYPE_UPDATE), $table->getName(),
            $data,
            $rowInitializer
        );
        
        return $this;
    }

    /**
     * Schedules a delete operation
     *
     * @param string $table
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleDelete($table, $condition = array(), $queue = self::QUEUE_PRIMARY)
    {
        $table = $this->schema->getTableInfo($table);

        $rowInitializer = function ($rowIndex, $condition, $key, $tableName) use ($table) {
            $tableKey = $this->implodeKey($key, $tableName);
            $columns = $table->getColumns();
            foreach ($columns as $column) {
                $columnName = $column->getName();
                if (array_key_exists($columnName, $condition)) {
                    if (is_array($condition[$columnName])) {
                        $conditionKey = $columnName . ' IN(?)';
                    } elseif ($condition[$columnName] === null) {
                        $conditionKey = $columnName . ' IS NULL';
                    } else {
                        $conditionKey = $columnName . ' = ?';
                    }

                    $condition[$conditionKey] = $condition[$columnName];
                    unset($condition[$columnName]);
                }
            }
            
            foreach ($condition as $conditionKey => $value) {
                if (is_array($value)) {
                    foreach ($value as $index => $item) {
                        if ($item instanceof MapInterface) {
                            $this->registerConditionMap($item, $conditionKey, $tableKey, $rowIndex, $index);
                        }
                    }
                } elseif ($value instanceof MapInterface) {
                    $this->registerConditionMap($value, $conditionKey, $tableKey, $rowIndex);
                }
            }
            
            return $condition;
        };

        $this->initializeScheduleRow(
            $this->implodeKey($queue, self::TYPE_DELETE), 
            $table->getName(),
            $condition,
            $rowInitializer
        );
        
        return $this;
    }

    /**
     * Returns resolved schedule items for table and type
     *
     * @param int $queue
     * @param string $table
     * @param string $type
     * @return array
     */
    public function getSchedule($queue, $table, $type)
    {
        $result = array();
        $scheduleKey = $this->implodeKey($queue, $type);
        $resolvedKey = $this->implodeKey($scheduleKey, $table);
        
        if (isset($this->schedule[$scheduleKey][$table]) 
            && isset($this->resolvedSchedule[$resolvedKey])) {
            foreach ($this->resolvedSchedule[$resolvedKey] as $key => $rowIndex) {
                unset($this->resolvedSchedule[$resolvedKey][$key]);
                if (isset($this->schedule[$scheduleKey][$table][$rowIndex])) {
                    $result[] = $this->schedule[$scheduleKey][$table][$rowIndex];
                    unset($this->schedule[$scheduleKey][$table][$rowIndex]);
                }
            }
        }
        
        return $result;
    }

    /**
     * Checks if it is possible to use multiple inserts for an insert schedule
     *
     * @param int $queue
     * @param string $table
     * @return bool
     */
    public function isInsertScheduleMultiple($queue, $table)
    {
        $tableKey = $this->implodeKey($queue, self::TYPE_INSERT, $table);
        if (!empty($this->resolvedSchedule[$tableKey])) {
            if (empty($this->unresolvedSchedulePrimaryKey[$tableKey]) 
                || !array_intersect(
                        $this->resolvedSchedule[$tableKey], 
                        $this->unresolvedSchedulePrimaryKey[$tableKey])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Returns schedule primary key map instance if it was passed before
     * into writer
     *
     * @param int $queue
     * @param string $table
     * @param int $rowIndex
     * @return MapInterface|bool
     */
    public function getInsertSchedulePrimaryKeyMap($queue, $table, $rowIndex)
    {
        $key = $this->implodeKey($queue, self::TYPE_INSERT, $table);
        if (isset($this->unresolvedSchedulePrimaryKey[$key]) 
            && ($objectId = array_search((int)$rowIndex, $this->unresolvedSchedulePrimaryKey[$key], true))
            && isset($this->knownMaps[$objectId])) {
            return $this->knownMaps[$objectId];
        }
        
        return false;
    }

    /**
     * Should return true if there is any schedule items left in queue
     *
     * @param int $queue
     * @param string $table
     * @param string $type
     * @return bool
     */
    public function hasSchedule($queue, $table, $type)
    {
        $key = $this->implodeKey($queue, $type);
        
        if (!empty($this->schedule[$key][$table])) {
            return true;
        }
        
        return false;
    }

    /**
     * Should return list of schedule resolve errors
     *
     * Will return list of errors only if there are any items left in schedule
     *
     * @param int $queue
     * @param string $table
     * @param string $type
     * @return \EcomDev_Fixture_Contract_Db_Writer_ErrorInterface[]
     */
    public function getScheduleResolveErrors($queue, $table, $type)
    {
        $result = array();
        if ($this->hasSchedule($queue, $table, $type)) {
            $key = $this->implodeKey($queue, $type, $table);
            if (!empty($this->unresolvedScheduleColumn[$key])) {
                foreach ($this->unresolvedScheduleColumn[$key] as $row => $columns) {
                    foreach ($columns as $column => $mapId) {
                        if (isset($this->knownMaps[$mapId])) {
                            $result[] = new EcomDev_Fixture_Db_Writer_Error(
                                sprintf('Column "%s" has unresolved map to external entity', $column),
                                $type,
                                $table,
                                $queue,
                                $row,
                                array(
                                    'map' => $this->knownMaps[$mapId]
                                )
                            );
                        }
                    }
                }
            }
            if (!empty($this->unresolvedScheduleCondition[$key])) {
                foreach ($this->unresolvedScheduleCondition[$key] as $row => $conditions) {
                    foreach ($conditions as $condition => $maps) {
                        foreach ($maps as $mapId => $index) {
                            if (isset($this->knownMaps[$mapId])) {
                                $result[] = new EcomDev_Fixture_Db_Writer_Error(
                                    $index === true 
                                        ? sprintf('Condition "%s" has unresolved map to external entity', $condition) 
                                        : sprintf('Condition "%s" has unresolved map to external entity at "%d" index', $condition, $index),
                                    $type,
                                    $table,
                                    $queue,
                                    $row,
                                    array(
                                        'map' => $this->knownMaps[$mapId]
                                    )
                                );
                            }   
                        }
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * Returns list of tables that exists in current schedule
     *
     * @param int $queue
     * @param string $type
     * @return string[]
     */
    public function getScheduleTables($queue, $type)
    {
        $key = $this->implodeKey($queue, $type);
        $result = array();
        if (isset($this->schedule[$key])) {
            $sortedTables = array_flip(
                $this->schema->getTableNamesSortedByRelation()
            );
            
            foreach ($this->schedule[$key] as $table => $data) {
                if (isset($sortedTables[$table])) {
                    $result[$sortedTables[$table]] = $table;
                }
            }
            
            ksort($result);
        }
        
        return $result;
    }

    /**
     * Resets object state
     *
     * @return $this
     */
    public function reset()
    {
        foreach ($this->knownMaps as $map) {
            if ($map instanceof EcomDev_Fixture_Contract_Utility_ResetAwareInterface) {
                $map->reset();
            }
        }
        
        $this->knownMaps = array();
        $this->schedule = array();
        $this->scheduleColumnMap = array();
        $this->scheduleConditionMap = array();
        $this->resolvedSchedule = array();
        $this->resolvedMaps = array();
        $this->unresolvedSchedulePrimaryKey = array();
        $this->unresolvedScheduleColumn = array();
        $this->unresolvedScheduleCondition = array();
        return $this;
    }
    
    /**
     * Prepares column value for schedule
     *
     * @param TableInterface $table
     * @param ColumnInterface $column
     * @param mixed $value
     * @return null|string|MapInterface
     */
    public function prepareColumnValue(TableInterface $table, ColumnInterface $column, $value)
    {
        if (is_array($value)) {
            if (isset($value[self::VALUE_SERIALIZED])) {
                $value = serialize($value[self::VALUE_SERIALIZED]);
            } elseif (isset($value[self::VALUE_JSON])) {
                $value = json_encode($value[self::VALUE_JSON]);
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid value supplied for "%s" in "%s". Supplied value is "%"',
                        $column->getName(),
                        $table->getName(),
                        print_r($value, true)
                    )
                );
            }
        } elseif (!$value instanceof MapInterface) {
            $value = $column->getRecommendedValue($value);
        }
        
        return $value;
    }

    /**
     * Returns combined key for an array
     * 
     * @param mixed [$keyPart1..]
     * @return string
     */
    public function implodeKey()
    {
        $items = func_get_args();
        return implode('/', $items);
    }

    /**
     * Returns combined key data
     * 
     * @param string $key
     * @param null|int $limit
     * @return array
     */
    public function explodeKey($key, $limit = null)
    {
        if ($limit !== null) {
            return explode('/', $key, $limit);    
        }
        
        return explode('/', $key);
    }

    /**
     * Registers map inside of internal maps storage
     * 
     * @param MapInterface $map
     * @return $this
     */
    public function registerMap(MapInterface $map)
    {
        $objectId = spl_object_hash($map);
        
        $this->knownMaps[$objectId] = $map;
        
        if ($map instanceof EcomDev_Fixture_Contract_Utility_NotifierAwareInterface) {
            $map->addNotifier($this);
        }
        
        if ($map->isResolved()) {
            $this->resolvedMaps[$objectId] = $objectId;
        }
        
        return $this;
    }

    /**
     * Registers column map into schedule and column map
     * 
     * @param MapInterface $map
     * @param ColumnInterface $column
     * @param string $key
     * @param int $rowIndex
     * @return $this
     */
    public function registerColumnMap($map, ColumnInterface $column, $key, $rowIndex)
    {
        $this->registerMap($map);
        $objectId = spl_object_hash($map);
        $scheduleKey = $this->implodeKey($key, $rowIndex, $column->getName());
        
        $this->scheduleColumnMap[$objectId][$scheduleKey] = true;
        
        if ($column->isPrimary() && $column->isIdentity()) {
            $this->unresolvedSchedulePrimaryKey[$key][$objectId] = $rowIndex;
        } else {
            $this->unresolvedScheduleColumn[$key][$rowIndex][$column->getName()] = $objectId;
        }
        
        return $this;
    }

    /**
     * Registers condition map
     * 
     * @param MapInterface $map
     * @param string $condition
     * @param string $key
     * @param int $rowIndex
     * @param null|int $index
     * @return $this
     */
    public function registerConditionMap($map, $condition, $key, $rowIndex, $index = null)
    {
        $this->registerMap($map);
        $objectId = spl_object_hash($map);
        $scheduleKey = $this->implodeKey($key, $rowIndex, $condition);
        $conditionKey = ($index === null ? true : $index);
        
        $this->scheduleConditionMap[$objectId][$scheduleKey] = $conditionKey;
        $this->unresolvedScheduleCondition[$key][$rowIndex][$condition][$objectId] = $conditionKey;
        return $this;
    }

    /**
     * Resolves schedule by key and row index
     * 
     * @param string $key
     * @param int $rowIndex
     * @return $this
     */
    public function resolveSchedule($key, $rowIndex)
    {
        $isResolvedColumn = empty($this->unresolvedScheduleColumn[$key][$rowIndex]);
        $isResolvedCondition = empty($this->unresolvedScheduleCondition[$key][$rowIndex]);
        
        if ($isResolvedColumn && $isResolvedCondition) {
            $this->resolvedSchedule[$key][$rowIndex] = $rowIndex;
            if (isset($this->unresolvedScheduleColumn[$key][$rowIndex])) {
                unset($this->unresolvedScheduleColumn[$key][$rowIndex]);
            }
            if (isset($this->unresolvedScheduleCondition[$key][$rowIndex])) {
                unset($this->unresolvedScheduleCondition[$key][$rowIndex]);
            }
        }
        return $this;
    }

    /**
     * Resolves maps that has been added into resolve maps array
     * 
     * 
     * @return $this
     */
    public function resolve()
    {
        if (empty($this->resolvedMaps)) {
            return $this;
        }
        
        foreach ($this->resolvedMaps as $mapId) {
            $map = $this->knownMaps[$mapId];
            $value = $map->getValue();
            unset($this->resolvedMaps[$mapId]);
            $this->resolveColumnMap($mapId, $value);
            $this->resolveConditionMap($mapId, $value);
        }
        
        return $this;
    }

    /**
     * Resolves column map in schedule lists
     * 
     * @param string $mapId
     * @param string $value
     * @return $this
     */
    protected function resolveColumnMap($mapId, $value) 
    {
        if (!isset($this->scheduleColumnMap[$mapId])) {
            return $this;
        }

        foreach ($this->scheduleColumnMap[$mapId] as $key => $flag) {
            list($queue, $type, $table, $rowId, $column) = $this->explodeKey($key, 5);
            $tableKey = $this->implodeKey($queue, $type, $table);
            $isUpdate = $type === self::TYPE_UPDATE;
            $isInsert = $type === self::TYPE_INSERT;
            if ($isInsert && isset($this->schedule[$tableKey][$rowId][$column])) {
                $this->schedule[$tableKey][$rowId][$column] = $value;
            } elseif ($isUpdate && isset($this->schedule[$tableKey][$rowId]['data'][$column])) {
                $this->schedule[$tableKey][$rowId]['data'][$column] = $value;
            }
            if (isset($this->unresolvedScheduleColumn[$tableKey][$rowId][$column])) {
                unset($this->unresolvedScheduleColumn[$tableKey][$rowId][$column]);
                $this->resolveSchedule($tableKey, $rowId);
            }
            if (isset($this->unresolvedSchedulePrimaryKey[$tableKey][$mapId])) {
                unset($this->unresolvedSchedulePrimaryKey[$tableKey][$mapId]);
            }
        }
        
        return $this;
    }

    /**
     * Resolves condition map in schedule lists
     *
     * @param string $mapId
     * @param string $value
     * @return $this
     */
    protected function resolveConditionMap($mapId, $value)
    {
        if (!isset($this->scheduleConditionMap[$mapId])) {
            return $this;
        }

        foreach ($this->scheduleConditionMap[$mapId] as $key => $index) {
            list($queue, $type, $table, $rowId, $condition) = $this->explodeKey($key, 5);
            $tableKey = $this->implodeKey($queue, $type, $table);
            $isUpdate = $type === self::TYPE_UPDATE;
            $isDelete = $type === self::TYPE_DELETE;
            if ($isDelete && isset($this->schedule[$tableKey][$rowId][$condition])) {
                if ($index === true) {
                    $this->schedule[$tableKey][$rowId][$condition] = $value;
                } else {
                    $this->schedule[$tableKey][$rowId][$condition][$index] = $value;
                }
            } elseif ($isUpdate && isset($this->schedule[$tableKey][$rowId]['condition'][$condition])) {
                if ($index === true) {
                    $this->schedule[$tableKey][$rowId]['condition'][$condition] = $value;
                } else {
                    $this->schedule[$tableKey][$rowId]['condition'][$condition][$index] = $value;
                }
            }

            if (isset($this->unresolvedScheduleCondition[$tableKey][$rowId][$condition][$mapId])) {
                unset($this->unresolvedScheduleCondition[$tableKey][$rowId][$condition][$mapId]);
                if (empty($this->unresolvedScheduleCondition[$tableKey][$rowId][$condition])) {
                    unset($this->unresolvedScheduleCondition[$tableKey][$rowId][$condition]);
                }
                $this->resolveSchedule($tableKey, $rowId);
            }
        }

        return $this;
    }
    
    /**
     * Receives notifications about changes in map
     * 
     * @param object $object
     * @param string $operation
     * @param mixed $data
     * @return $this
     */
    public function notify($object, $operation, $data = null)
    {
        $objectId = spl_object_hash($object);
        if ($object instanceof MapInterface && isset($this->knownMaps[$objectId])) {
            switch ($operation) {
                case 'resolve':
                    if ($data === true) {
                        $this->resolvedMaps[$objectId] = $objectId;
                    }
                    break;
                case 'reset':
                    if ($object instanceof EcomDev_Fixture_Contract_Utility_NotifierAwareInterface) {
                        $object->removeNotifier($this);
                    }
                    break;
            }
        }
        
        return $this;
    }
}
