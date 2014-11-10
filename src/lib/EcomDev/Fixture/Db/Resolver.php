<?php

use EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface as ContainerInterface;
use EcomDev_Fixture_Contract_Db_Resolver_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_Schema_ColumnInterface as ColumnInterface;
use Varien_Db_Adapter_Interface as AdapterInterface;
use EcomDev_Fixture_Db_Resolver_Container as Container;


class EcomDev_Fixture_Db_Resolver
    implements EcomDev_Fixture_Contract_Db_ResolverInterface,
               EcomDev_Fixture_Contract_Utility_NotifierAwareInterface
{

    /**
     * Database adapter
     * 
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Container for map storage
     * 
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Schema object
     * 
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Notifiers container
     * 
     * @var EcomDev_Fixture_Utility_Notifier_Container
     */
    protected $notifiers;
    
    /**
     * Instantiates a resolver instance
     *
     * @param AdapterInterface $adapter
     * @param SchemaInterface $schema
     * @param ContainerInterface|null $container
     */
    public function __construct(AdapterInterface $adapter, 
                                SchemaInterface $schema, 
                                ContainerInterface $container = null)
    {
        if ($container === null) {
            $container = new Container();
        }
        
        $this->adapter = $adapter;
        $this->container = $container;
        $this->schema = $schema;
        $this->notifiers = new EcomDev_Fixture_Utility_Notifier_Container();
    }

    /**
     * @param $typeOrTable
     * @param array|string $condition
     * @return MapInterface
     */
    public function map($typeOrTable, $condition)
    {
        return $this->container->map($typeOrTable, $condition);
    }

    /**
     * @param $typeOrTable
     * @param array $row
     * @return MapInterface
     */
    public function mapRow($typeOrTable, $row)
    {
        return $this->container->mapRow($typeOrTable, $row);
    }

    /**
     * Returns true if it is allowed to map rows for this table
     *
     * @param string $table
     * @return boolean
     */
    public function canMapRow($table)
    {
        return $this->container->canMapRow($table);
    }

    /**
     * Resolves mapped ids for a table
     *
     * @param string $table
     * @return MapInterface[]
     */
    public function resolve($table)
    {
        $maps = $this->container->unresolved($table);
        
        if (!$maps) {
            return $this;
        }
        
        $conditionGroup = array();
        $whereConditions = array();
        
        foreach ($maps as $map) {
            $mapTable = $map->getTable();
            if (is_array($map->getConditionField())) {
                $groupKey = (string) $this->adapter->getConcatSql(
                    array_map(
                        array($this->adapter, 'quoteIdentifier'), 
                        $map->getConditionField()
                    ),
                    '|'
                );
                $whereConditions[$mapTable][$groupKey] = $groupKey;
                $itemKey = implode('|', $map->getConditionValue());
            } else {
                $groupKey = $map->getConditionField();
                $whereConditions[$mapTable][$groupKey] = $this->adapter->quoteIdentifier($groupKey);
                $itemKey = $map->getConditionValue();
            }
            
            $conditionGroup[$mapTable][$groupKey][$itemKey] = $map;
        }
        
        foreach ($conditionGroup as $groupTable => $condition) {
            $primaryKeyColumn = $this->schema->getTableInfo($groupTable)->getPrimaryKeyColumn();
            $customCondition = array();
            if (!$primaryKeyColumn instanceof ColumnInterface) {
                $data = new stdClass();
                $data->customCondition = $customCondition;
                $data->resolveTable = $table;
                $data->mapTable = $groupTable;
                $data->primaryColumn = '';
                $this->notifiers->notify($this, 'resolve_table', $data);
                $primaryColumn = $data->primaryColumn;
                $customCondition = $data->customCondition;
            } else {
                $primaryColumn = $primaryKeyColumn->getName();
            }
            
            foreach ($condition as $groupKey => $items) {
                $select = $this->getAdapter()->select();
                
                $select->from(
                    $groupTable,
                    array($groupKey, $primaryColumn)
                );
                
                $select->where(
                    $whereConditions[$groupTable][$groupKey] . ' IN(?)', array_keys($items)
                );
                
                foreach ($customCondition as $conditionKey => $conditionValue) {
                    $select->where(
                        $this->adapter->quoteIdentifier($conditionKey) . ' = ?', $conditionValue
                    );
                }
                
                foreach ($this->adapter->fetchPairs($select) as $key => $value) {
                    $items[$key]->setValue($value);
                }
            }
        }
        
        return $this;
    }

    /**
     * Resets db resolver container
     *
     * @return $this
     */
    public function reset()
    {
        $this->container->reset();
        return $this;
    }

    /**
     * Returns a container instance used by resolver
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Returns an adapter instance used by resolver
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
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
}
