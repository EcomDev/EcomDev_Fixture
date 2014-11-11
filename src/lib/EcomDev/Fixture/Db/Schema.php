<?php

use EcomDev_Fixture_Contract_Db_Schema_TableInterface as TableInterface;
use EcomDev_Fixture_Contract_Db_Schema_CacheProviderInterface as CacheProviderInterface;
use EcomDev_Fixture_Contract_Db_Schema_InformationProviderInterface as InformationProviderInterface;

class EcomDev_Fixture_Db_Schema 
    implements EcomDev_Fixture_Contract_Db_SchemaInterface, Serializable
{
    
    /** 
     * Root table, that does not have any relations
     *
     * @type int 
     */
    const RELATION_ROOT = 1;

    /**
     * Parent table, that does not have any references internally
     * 
     * @type int
     */
    const RELATION_PARENT = 2;

    /**
     * Child table, that does not have any external references
     * 
     * @type int
     */
    const RELATION_CHILD = 4;

    /**
     * Table that has both references, external and internal
     * 
     * @type int
     */
    const RELATION_BOTH = 6;
    
    /**
     * @var InformationProviderInterface
     */
    protected $informationProvider;

    /**
     * @var string
     */
    protected $tableInfoClass;

    /**
     * @var TableInterface[]
     */
    protected $tables;

    /**
     * @var string[][][]
     */
    protected $tableRelations;

    /**
     * @var string[]
     */
    protected $tableNamesSortedByRelation;

    /**
     * Tables separated by types of relation
     * 
     * @example
     *  array(
     *     self::RELATION_ROOT => array() // List of tables without any foreign keys
     *     self::RELATION_PARENT => array(), // Tables referenced in foreign key
     *     self::RELATION_BOTH => array() // Tables that are both referenced and referencing
     *     self::RELATION_CHILD => array(), // Tables that are referencing only tables in foreign key 
     *  )
     * 
     * @var string[][]
     */
    protected $tablesSeparatedByRelationType;

    
    /**
     * Constructor of the info object
     *
     * @param InformationProviderInterface $informationProvider
     * @param string|null $tableInfoClass
     */
    public function __construct(InformationProviderInterface $informationProvider, $tableInfoClass = null)
    {
        $this->informationProvider = $informationProvider;
        
        if ($tableInfoClass === null) {
            $tableInfoClass = 'EcomDev_Fixture_Db_Schema_Table';
        }
        
        $this->setTableInfoClass($tableInfoClass);
    }

    /**
     * Returns an instance of adapter
     *
     * @return InformationProviderInterface
     */
    public function getInformationProvider()
    {
        return $this->informationProvider;
    }

    /**
     * Returns table info class
     *
     * @return string
     */
    public function getTableInfoClass()
    {
        return $this->tableInfoClass;
    }

    /**
     * Sets table info class
     *
     * @param string $className
     * @return $this
     */
    public function setTableInfoClass($className)
    {
        $this->tableInfoClass = $className;
        return $this;
    }

    /**
     * Return table information object
     *
     * @param string $tableName
     * @return EcomDev_Fixture_Contract_Db_Schema_TableInterface
     * @throws EcomDev_Fixture_Db_Schema_Exception if $tableName is not found
     */
    public function getTableInfo($tableName)
    {
        $this->fetch();
        
        if (!isset($this->tables[$tableName])) {
            throw new EcomDev_Fixture_Db_Schema_Exception(
                sprintf(
                    'Requested table "%s" does not exists.',
                    $tableName
                ),
                EcomDev_Fixture_Db_Schema_Exception::TABLE_NOT_FOUND
            );
        }
        
        return $this->tables[$tableName];
    }

    /**
     * Return list of all parent table objects
     *
     * @param string $tableName
     * @return EcomDev_Fixture_Contract_Db_Schema_TableInterface[]
     * @throws EcomDev_Fixture_Db_Schema_Exception if $tableName is not found
     */
    public function getTableAncestors($tableName)
    {
        $this->fetchTableRelations();
        $table = $this->getTableInfo($tableName);
        $result = array();
        foreach ($this->tableRelations[$table->getName()]['ancestors'] as $parentName) {
            $result[$parentName] = $this->getTableInfo($parentName);
        }
        return $result;
    }

    /**
     * Return list of child tables
     *
     * @param string $tableName
     * @return EcomDev_Fixture_Contract_Db_Schema_TableInterface[]
     * @throws EcomDev_Fixture_Db_Schema_Exception if $tableName is not found
     */
    public function getTableDescendants($tableName)
    {
        $this->fetchTableRelations();
        $table = $this->getTableInfo($tableName);
        $result = array();
        foreach ($this->tableRelations[$table->getName()]['descendants'] as $childName) {
            $result[$childName] = $this->getTableInfo($childName);
        }
        return $result;
    }

    /**
     * Return list of all tables available in the database
     *
     * Tables are ordered alphabetically
     *
     * @return string[]
     */
    public function getTableNames()
    {
        $this->fetch();
        return array_keys($this->tables);
    }

    /**
     * Returns all table names, sorted by relation
     *
     * @return string[]
     */
    public function getTableNamesSortedByRelation()
    {
        if ($this->tableNamesSortedByRelation === null) {
            $this->fetchTableRelations();
            
            $listToSort = $this->tablesSeparatedByRelationType[self::RELATION_BOTH];
            $checkArray = array_combine($listToSort, $listToSort); // Key => Value map
            
            foreach ($listToSort as $tableName) {
                $currentPosition = array_search($tableName, $listToSort);
                $afterPosition = $currentPosition;
                
                foreach ($this->tableRelations[$tableName]['ancestors'] as $parentTable) {
                    if (!isset($checkArray[$parentTable])) {
                        continue;
                    }
                    
                    $afterPosition = max(
                        $afterPosition, 
                        array_search(
                            $parentTable,
                            $listToSort
                        )
                    );
                }
                
                if ($currentPosition < $afterPosition) {
                    array_splice($listToSort, $afterPosition + 1, 0, array($tableName));
                    array_splice($listToSort, $currentPosition, 1, array(false));
                    $currentPosition = $afterPosition + 1;
                }
                
                foreach ($this->tableRelations[$tableName]['descendants'] as $childTable) {
                    if (!isset($checkArray[$childTable])) {
                        continue;
                    }
                    
                    $beforePosition = array_search($childTable, $listToSort);

                    if ($currentPosition > $beforePosition) {
                        array_splice($listToSort, $currentPosition + 1, 0, array($childTable));
                        array_splice($listToSort, $beforePosition, 1, array(false));
                    }
                }
            }
                        
            $this->tableNamesSortedByRelation = array_merge(
                $this->tablesSeparatedByRelationType[self::RELATION_ROOT],
                $this->tablesSeparatedByRelationType[self::RELATION_PARENT],
                array_filter($listToSort),
                $this->tablesSeparatedByRelationType[self::RELATION_CHILD]
            );
        }
        
        return $this->tableNamesSortedByRelation;
    }

    /**
     * Resets all the information about all the tables
     *
     * @return $this
     */
    public function reset()
    {
        $this->tables = null;
        $this->tableRelations = null;
        $this->tableNamesSortedByRelation = null;
        $this->informationProvider->reset();
        return $this;
    }

    /**
     * Fetches data from the database
     * 
     * 
     */
    public function fetch()
    {
        if ($this->tables !== null) {
            return $this;
        }
        
        $this->tables = array();
        
        $tableNames = $this->getInformationProvider()->getTableNames();
        $relationsChild = array();
        
        foreach ($tableNames as $tableName) {
            $columns = $this->getInformationProvider()->getColumns($tableName);
            $keys = $this->getInformationProvider()->getIndexes($tableName);
            $foreignKeys = $this->getInformationProvider()->getForeignKeys($tableName);
            
            /* @var $table EcomDev_Fixture_Contract_Db_Schema_TableInterface */
            $table = new $this->tableInfoClass(
                $tableName, $columns, $keys, $foreignKeys
            );
            
            $this->tables[$table->getName()] = $table;
            $parentTables = array();
            foreach ($table->getForeignKeys() as $foreignKey) {
                if (in_array($foreignKey->getReferenceTable(), $tableNames, true)) {
                    $relationsChild[$foreignKey->getReferenceTable()][] = $table->getName();
                    $parentTables[] = $foreignKey->getReferenceTable();
                }
            }
            $parentTables = array_unique($parentTables);
            $table->setParentTables($parentTables);
        }
        
        foreach ($relationsChild as $tableName => $childTables) {
            $this->tables[$tableName]->setChildTables($childTables);
        }
        
        ksort($this->tables, SORT_ASC);
        
        return $this;
    }

    /**
     * Fetches table relations by getting all ancestors and child tables for all tables
     * Also separates table by relation types
     * 
     * @return $this
     */
    public function fetchTableRelations()
    {
        if ($this->tableRelations !== null) {
            return $this;
        }
        
        $this->fetch();
        $this->tableRelations = array();
        $this->tablesSeparatedByRelationType = array(
            self::RELATION_ROOT => array(),
            self::RELATION_PARENT => array(),
            self::RELATION_BOTH => array(),
            self::RELATION_CHILD => array()
        );
        
        foreach ($this->tables as $tableName => $table) {
            $this->tableRelations[$tableName]['ancestors'] = $this->collectRelationTables(
                $table->getParentTables(), $table, 'getParentTables' 
            );

            $this->tableRelations[$tableName]['descendants'] = $this->collectRelationTables(
                $table->getChildTables(), $table, 'getChildTables' 
            );

            $type = self::RELATION_ROOT;
            
            $hasChild = !empty($this->tableRelations[$tableName]['descendants']);
            $hasParent = !empty($this->tableRelations[$tableName]['ancestors']);
            
            if ($hasChild || $hasParent) {
                $type = ( $hasChild ? self::RELATION_PARENT : 0 ) + ( $hasParent ? self::RELATION_CHILD : 0 );
            }

            $this->tablesSeparatedByRelationType[$type][] = $tableName;
        }
        
        return $this;
    }

    /**
     * Collects recursivelly all related tables for required type of tables
     * 
     * @param string[] $tables
     * @param TableInterface $table
     * @param string $method method name to call on table, for recursively obtain children/parent
     * @return array
     */
    protected function collectRelationTables($tables, $table, $method)
    {
        $result = array();

        while ($tables) {
            $relatedTable = $this->tables[array_shift($tables)];
            if ($relatedTable !== $table) {
                $result[] = $relatedTable->getName();

                foreach ($relatedTable->$method() as $relatedOfRelatedTable) {
                    // Save it from endless loop on multi-linked tables
                    if (!in_array($relatedOfRelatedTable, $result)) {
                        $tables[] = $relatedOfRelatedTable;
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $this->fetchTableRelations();
        
        $data = array(
            'tables' => $this->tables,
            'tableNamesSortedByRelation' => $this->tableNamesSortedByRelation,
            'tablesSeparatedByRelationType' => $this->tablesSeparatedByRelationType,
            'tableRelations' => $this->tableRelations,
            'tableInfoClass' => $this->tableInfoClass
        );
        
        return serialize($data);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->tables = $data['tables'];
        $this->tableNamesSortedByRelation = $data['tableNamesSortedByRelation'];
        $this->tablesSeparatedByRelationType = $data['tablesSeparatedByRelationType'];
        $this->tableRelations = $data['tableRelations'];
        $this->tableInfoClass = $data['tableInfoClass'];
    }
}
