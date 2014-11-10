<?php

class EcomDev_Fixture_Db_Schema_InformationProvider 
    implements EcomDev_Fixture_Contract_Db_Schema_InformationProviderInterface
{
    /**
     * Database Adapter
     * 
     * @var Varien_Db_Adapter_Interface|Zend_Db_Adapter_Abstract
     */
    protected $adapter;

    /**
     * List of table names available in current database
     * 
     * @var array
     */
    protected $tableNames = array();

    /**
     * List of fetched column data
     * 
     * @var array
     */
    protected $columns = array();

    /**
     * List of fetched indexes data
     * 
     * @var array
     */
    protected $indexes = array();

    /**
     * List of fetched foreign keys
     * 
     * @var array
     */
    protected $foreignKeys = array();

    /**
     * Flag if the data have been fetched
     * 
     * @var bool
     */
    protected $loaded = false;
    
    /**
     * Constructor of the information provider
     *
     * @param Varien_Db_Adapter_Interface $adapter
     */
    public function __construct(Varien_Db_Adapter_Interface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Loads data from database
     *
     * @return $this
     */
    public function load()
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $this->tableNames = $this->adapter->listTables();
            $this->columns = $this->fetchColumns();
            $this->indexes = $this->fetchIndexes();
            $this->foreignKeys = $this->fetchForeignKeys();
        }
        
        return $this;
    }

    /**
     * Builds select and loads data from information schema table
     * 
     * @param string|array $table
     * @param Closure|null $selectModifier
     * @return array
     */
    protected function loadInformationSchema($table, $selectModifier = null)
    {
        $config = $this->adapter->getConfig();
        $select = $this->adapter->select();
        $select->from($table, '*', 'information_schema');
        
        if ($selectModifier instanceof Closure) {
            $selectModifier($select, $config['dbname']);
        } else {
            $select->where('TABLE_SCHEMA = ?', $config['dbname']);
        }
        
        return $this->adapter->fetchAll($select);
    }

    /**
     * Fetches all the columns of all the tables in database
     * 
     * @return array
     */
    protected function fetchColumns()
    {
        $data = $this->loadInformationSchema('columns');
        $result = array_combine($this->tableNames, array_pad(array(), count($this->tableNames), array()));
        $tablePrimaryIndex = array();
        foreach ($data as $row) {
            $tablePrimaryIndex[$row['TABLE_NAME']] = 1;
            $result[$row['TABLE_NAME']][$row['COLUMN_NAME']] = array(
                'SCHEMA_NAME' => null,
                'TABLE_NAME' => $row['TABLE_NAME'],
                'COLUMN_NAME' => $row['COLUMN_NAME'],
                'COLUMN_POSITION' => (int)$row['ORDINAL_POSITION'],
                'DATA_TYPE' => $row['DATA_TYPE'],
                'DEFAULT' => $row['COLUMN_DEFAULT'],
                'NULLABLE' => $row['IS_NULLABLE'] == 'YES',
                'LENGTH' => strpos($row['COLUMN_TYPE'], '(') !== false ? $row['CHARACTER_MAXIMUM_LENGTH'] : null,
                'SCALE' => strpos($row['DATA_TYPE'], 'int') === false ? $row['NUMERIC_SCALE'] : null,
                'PRECISION' => strpos($row['DATA_TYPE'], 'int') === false ? $row['NUMERIC_PRECISION'] : null,
                'UNSIGNED' => strpos($row['COLUMN_TYPE'], 'unsigned') !== false ?: null,
                'PRIMARY' => $row['COLUMN_KEY'] === 'PRI', 
                'PRIMARY_POSITION' => $row['COLUMN_KEY'] === 'PRI' ? $tablePrimaryIndex[$row['TABLE_NAME']] : null,
                'IDENTITY' => strpos($row['EXTRA'], 'auto_increment') !== false
            );
            
            $tablePrimaryIndex[$row['TABLE_NAME']] += $row['COLUMN_KEY'] === 'PRI' ? 1 : 0;
        }
        return $result;
    }

    /**
     * Fetches information about all the table regular indexes
     * 
     * @return array
     */
    protected function fetchIndexes()
    {
        $data = $this->loadInformationSchema('statistics');
        $result = array_combine($this->tableNames, array_pad(array(), count($this->tableNames), array()));
        $columnList = array();
        foreach ($data as $row) {
            $columnList[$row['TABLE_NAME']][$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
        }

        foreach ($data as $row) {
            $indexType = Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX;
            if ($row['INDEX_NAME'] === 'PRIMARY') {
                $indexType = Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY;
            } elseif ($row['NON_UNIQUE'] == '0') {
                $indexType = Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE;
            }

            if (!isset($result[$row['TABLE_NAME']][$row['INDEX_NAME']])) {
                $result[$row['TABLE_NAME']][$row['INDEX_NAME']] = array(
                    'SCHEMA_NAME' => null,
                    'TABLE_NAME' => $row['TABLE_NAME'],
                    'KEY_NAME' => $row['INDEX_NAME'],
                    'COLUMNS_LIST' => $columnList[$row['TABLE_NAME']][$row['INDEX_NAME']],
                    'INDEX_TYPE' => $indexType,
                    'INDEX_METHOD' => $row['INDEX_TYPE'],
                    'type' => $indexType,
                    'fields' => $columnList[$row['TABLE_NAME']][$row['INDEX_NAME']]
                );
            }
        }


        return $result;
    }

    /**
     * Fetches foreign keys information from information schema tables
     * 
     * @return array
     */
    protected function fetchForeignKeys()
    {
        $data = $this->loadInformationSchema(
            array('constraint' => 'referential_constraints'), 
            function (Zend_Db_Select $select, $dbName) {
                $select
                    ->join(
                        array('column' => 'key_column_usage'),
                        'column.CONSTRAINT_SCHEMA = constraint.CONSTRAINT_SCHEMA'
                        . ' AND column.CONSTRAINT_NAME = constraint.CONSTRAINT_NAME'
                        . ' AND column.TABLE_NAME = constraint.TABLE_NAME'
                        . ' AND column.REFERENCED_TABLE_NAME = constraint.REFERENCED_TABLE_NAME',
                        array(
                            'COLUMN_NAME',
                            'REFERENCED_COLUMN_NAME'
                        ),
                        'information_schema'
                    )
                    ->where('constraint.CONSTRAINT_SCHEMA = ?', $dbName); 
            }
        );
        
        $result = array_combine($this->tableNames, array_pad(array(), count($this->tableNames), array()));
        
        $columnName = array();
        $referenceColumnName = array();
        
        foreach ($data as $row) {
            $columnName[$row['TABLE_NAME']][$row['CONSTRAINT_NAME']][] = $row['COLUMN_NAME'];
            $referenceColumnName[$row['TABLE_NAME']][$row['CONSTRAINT_NAME']][] = $row['REFERENCED_COLUMN_NAME'];
        }

        foreach ($data as $row) {
            if (!isset($result[$row['TABLE_NAME']][$row['CONSTRAINT_NAME']])) {
                $columns = $columnName[$row['TABLE_NAME']][$row['CONSTRAINT_NAME']];
                $referenceColumns = $referenceColumnName[$row['TABLE_NAME']][$row['CONSTRAINT_NAME']];
                $result[$row['TABLE_NAME']][$row['CONSTRAINT_NAME']] = array(
                    'FK_NAME' => $row['CONSTRAINT_NAME'],
                    'SCHEMA_NAME' => null,
                    'TABLE_NAME' => $row['TABLE_NAME'],
                    'COLUMN_NAME' => implode(',', $columns),
                    'REF_SHEMA_NAME' => '',
                    'REF_TABLE_NAME' => $row['REFERENCED_TABLE_NAME'],
                    'REF_COLUMN_NAME' => implode(',', $referenceColumns),
                    'ON_DELETE' => $row['DELETE_RULE'],
                    'ON_UPDATE' => $row['UPDATE_RULE']
                );
            }
        }
        
        return $result;
    }

    /**
     * Returns list of table names
     *
     * @return string[]
     */
    public function getTableNames()
    {
        $this->load();
        return $this->tableNames;
    }

    /**
     * Returns list of columns
     *
     * @param string $tableName
     * @return array[]
     */
    public function getColumns($tableName)
    {
        $this->load();
        return $this->columns[$tableName];
    }

    /**
     * Returns list of indexes
     *
     * @param string $tableName
     * @return array[]
     */
    public function getIndexes($tableName)
    {
        $this->load();
        return $this->indexes[$tableName];
    }

    /**
     * Returns list of foreign keys
     *
     * @param string $tableName
     * @return array[]
     */
    public function getForeignKeys($tableName)
    {
        $this->load();
        return $this->foreignKeys[$tableName];
    }

    /**
     * Resets loaded flag for information provider
     * 
     * @return $this
     */
    public function reset()
    {
        $this->loaded = false;
        return $this;
    }
}
