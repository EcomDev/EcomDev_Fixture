<?php
use EcomDev_Fixture_Contract_Db_Resolver_MapInterface as MapInterface;
use EcomDev_Fixture_Utility_HashMap as HashMap;

class EcomDev_Fixture_Db_Resolver_Container 
    implements EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface,
               EcomDev_Fixture_Contract_Utility_NotifierInterface,
               EcomDev_Fixture_Contract_Utility_NotifierAwareInterface
{
    const CACHE_TABLE_NAME = 'table_name';
    const CACHE_ALL = 'all';
    const CACHE_ALL_TABLE = 'all_table';
    const CACHE_UNRESOLVED = 'unresolved';
    const CACHE_UNRESOLVED_TABLE = 'unresolved_table';

    protected $map;
    
    protected $defaultConditionField = array();
    
    protected $mapRowRule = array();
    
    protected $mapClass = 'EcomDev_Fixture_Db_Resolver_Map';
    
    protected $alias = array();
    
    protected $mapCache = array();

    /**
     * @var EcomDev_Fixture_Utility_Notifier_Container
     */
    protected $notifiers;
    
    public function __construct()
    {
        $this->map = new EcomDev_Fixture_Utility_HashMap();
        $this->notifiers = new EcomDev_Fixture_Utility_Notifier_Container();
        $this->mapCache = array(
            self::CACHE_ALL => array(),
            self::CACHE_UNRESOLVED => array()
        );
    }

    /**
     * Sets class for creation of new map instances
     *
     * @param string $className
     * @return $this
     * @throws InvalidArgumentException if class does not implement map interface
     */
    public function setMapClass($className)
    {
        if (!class_implements($className, 'EcomDev_Fixture_Contract_Db_Resolver_MapInterface')) {
           throw new InvalidArgumentException(
               sprintf(
                   '%s class should implement %s interface',
                   $className,
                   'EcomDev_Fixture_Contract_Db_Resolver_MapInterface'
               )
           );
        }
        
        $this->mapClass = $className;
        return $this;
    }
    
    /**
     * Creates a new map object based on specified arguments
     *
     * @param string $typeOrTable
     * @param string|array $condition
     * @return MapInterface
     */
    public function map($typeOrTable, $condition)
    {
        $table = $this->resolveAlias($typeOrTable);
        
        if (is_string($condition) && $this->getDefaultConditionField($table)) {
            $condition = array(
                $this->getDefaultConditionField($table) => $condition 
            );
        }
        
        $key = array(
            'table' => $table,
            'condition' => $condition
        );
        
        if (!isset($this->map[$key])) {
            $this->map[$key] = new $this->mapClass($table, $condition);
            
            $map = $this->map[$key]; 
            if ($map instanceof EcomDev_Fixture_Contract_Utility_NotifierAwareInterface) {
                $map->addNotifier($this);
            }
            
            $this->notifiers->notify($map, 'map', $table);
            $this->setMapCache($map, $map->isResolved(), $table);
        }
        
        return $this->map[$key];
    }

    /**
     * Sets cache for map objects
     * 
     * @param EcomDev_Fixture_Contract_Db_Resolver_MapInterface $map
     * @param $resolved
     * @param null $table
     * @return $this
     */
    protected function setMapCache(MapInterface $map, $resolved, $table = null)
    {
        $mapId = spl_object_hash($map);
        
        if ($table === null) {
            $table = $map->getTable();
        }
        
        if (isset($this->mapCache[self::CACHE_TABLE_NAME][$mapId])) {
            $table = $this->mapCache[self::CACHE_TABLE_NAME][$mapId];
        } else {
            $this->mapCache[self::CACHE_TABLE_NAME][$mapId] = $table;
        }
        
        $this->mapCache[self::CACHE_ALL][$mapId] = $map;
        $this->mapCache[self::CACHE_ALL_TABLE][$table][$mapId] = $map;
        
        if (!$resolved) {
            $this->mapCache[self::CACHE_UNRESOLVED][$mapId] = $map;
            $this->mapCache[self::CACHE_UNRESOLVED_TABLE][$table][$mapId] = $map;
        } else {
            if (isset($this->mapCache[self::CACHE_UNRESOLVED_TABLE][$table][$mapId])) {
                unset($this->mapCache[self::CACHE_UNRESOLVED_TABLE][$table][$mapId]);
            }
            if (isset($this->mapCache[self::CACHE_UNRESOLVED][$mapId])) {
                unset($this->mapCache[self::CACHE_UNRESOLVED][$mapId]);
            }
        }
        return $this;
    }

    /**
     * Returns alias name for alias name
     * 
     * @param string $aliasName
     * @return string
     */
    protected function resolveAlias($aliasName)
    {
        while (isset($this->alias[$aliasName]) && $this->alias[$aliasName] != $aliasName) {
            $aliasName = $this->alias[$aliasName];
        }
        return $aliasName;
    }

    /**
     * Creates a map instance for row, based on id field, or if it is not found, based on default field settings
     *
     * @param $table
     * @param array $row
     * @return MapInterface
     * @throws \RuntimeException if it is not possible to map it
     */
    public function mapRow($table, $row)
    {
        $table = $this->resolveAlias($table);
        
        if (!$this->canMapRow($table)) {
            throw new \RuntimeException(
                sprintf('There is no mapping data or default condition field is missing for "%s"', $table)
            );
        }
        
        $mapRule = $this->mapRowRule[$table];
        if (empty($mapRule)) {
            $mapRule = array($this->getDefaultConditionField($table) => null);
        }
        
        $condition = array();
        foreach ($mapRule as $field => $defaultValue) {
            if (isset($row[$field])) {
                $condition[$field] = $row[$field];
            } elseif ($defaultValue !== null) {
                $condition[$field] = $defaultValue;
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Value for "%s" in "%s" is missing and no default value provided', 
                        $field, $table
                    )
                );
            }
        }
        
        return $this->map($table, $condition);
    }

    

    /**
     * Returns true if it is allowed to map rows for this table
     *
     * @param string $table
     * @return boolean
     */
    public function canMapRow($table)
    {
        return isset($this->mapRowRule[$table]) 
            && (!empty($this->mapRowRule[$table]) || $this->getDefaultConditionField($table));
    }

    /**
     * Allows mapping by row
     *
     * If $conditionFields is empty,
     * it will try to use default field condition property for table
     *
     * Unless default field or condition fields are set,
     * it is not possible use mapRow method
     *
     * If $conditionFields is an associative array it will use its pair value as default for mapping
     *
     * @param string $table
     * @param array|null $conditionFields
     * @return $this
     */
    public function mapRowRule($table, array $conditionFields = array())
    {
        $this->mapRowRule[$table] = array();
        foreach ($conditionFields as $key => $field) {
            if (is_int($key)) {
                $this->mapRowRule[$table][$field] = null;
            } else {
                $this->mapRowRule[$table][$key] = $field; 
            }
        }
        
        return $this;
    }

    /**
     * Sets a default condition field for a table
     *
     * @param string $table
     * @param string $defaultField
     * @return $this
     */
    public function setDefaultConditionField($table, $defaultField)
    {
        $this->defaultConditionField[$table] = $defaultField;
        return $this;
    }

    /**
     * Returns default condition field
     *
     * @param string $table
     * @return string
     */
    public function getDefaultConditionField($table)
    {
        if (isset($this->defaultConditionField[$table])) {
            return $this->defaultConditionField[$table];
        }
        
        return false;
    }
    
    /**
     * Adds an alias for a table
     *
     * @param string $type
     * @param string $tableName
     * @return $this
     */
    public function alias($type, $tableName)
    {
        $this->alias[$type] = $tableName;
        return $this;
    }

    /**
     * Returns all unresolved maps
     *
     * If $table parameter is specified, it is filtered by table
     *
     * @param string|null $table
     * @return MapInterface[]
     */
    public function unresolved($table = null)
    {
        $result = array();
        if ($table === null) {
            $result = array_values($this->mapCache[self::CACHE_UNRESOLVED]);
        } else {
            $table = $this->resolveAlias($table);
            if (isset($this->mapCache[self::CACHE_UNRESOLVED_TABLE][$table])) {
                $result = array_values($this->mapCache[self::CACHE_UNRESOLVED_TABLE][$table]);
            }
        }
        
        return $result;
    }

    /**
     * Returns all maps
     *
     * If $table parameter is specified, it is filtered by table name
     *
     * @param string|null $table
     * @return MapInterface[]
     */
    public function all($table = null)
    {
        $result = array();
        if ($table === null) {
            $result = array_values($this->mapCache[self::CACHE_ALL]);
        } else {
            $table = $this->resolveAlias($table);
            if (isset($this->mapCache[self::CACHE_ALL_TABLE][$table])) {
                $result = array_values($this->mapCache[self::CACHE_ALL_TABLE][$table]);
            }
        }
        
        return $result;
    }

    /**
     * Resets container data
     *
     * @return $this
     */
    public function reset()
    {
        $this->map->reset();
        $this->mapCache = array(
            self::CACHE_ALL => array(),
            self::CACHE_UNRESOLVED => array()
        );
    }

    /**
     * Sets notifier instance
     *
     * @param EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier
     * @return $this
     */
    public function addNotifier(EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier)
    {
        $this->notifiers->add($notifier);
        return $this;
    }

    /**
     * Removes a notifier
     *
     * @param EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier
     * @return $this
     */
    public function removeNotifier(EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier)
    {
        $this->notifiers->remove($notifier);
        return $this;
    }

    /**
     * Returns list of notifiers
     *
     * @return EcomDev_Fixture_Contract_Utility_NotifierInterface[]
     */
    public function getNotifiers()
    {
        return $this->notifiers->items();
    }

    /**
     * Receives notifications from map objects about status changes
     * 
     * @param $object
     * @param $operation
     * @param mixed $data
     */
    public function notify($object, $operation, $data = null)
    {
        if ($object instanceof MapInterface) {
            switch ($operation) {
                case 'resolve':
                    $this->setMapCache($object, $data);
                    break;
            }
        }
    }
}
