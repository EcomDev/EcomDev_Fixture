<?php

use EcomDev_Fixture_Contract_Db_Schema_ColumnInterface as ColumnInterface;
use EcomDev_Fixture_Contract_Db_Schema_KeyInterface as KeyInterface;
use EcomDev_Fixture_Contract_Db_Schema_ForeignKeyInterface as ForeignKeyInterface;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Fixture_Db_Schema_Key as Key;
use EcomDev_Fixture_Db_Schema_ForeignKey as ForeignKey;

/**
 * Schema table definition object
 * 
 * 
 */
class EcomDev_Fixture_Db_Schema_Table 
    implements EcomDev_Fixture_Contract_Db_Schema_TableInterface
{
    /**
     * Table name
     * 
     * @var string
     */
    protected $name;

    /**
     * List of columns by column name in table
     * 
     * @var ColumnInterface[]
     */
    protected $columns = array();

    /**
     * List of foreign keys by name
     * 
     * @var ForeignKeyInterface[]
     */
    protected $foreignKeys = array();

    /**
     * List of keys by name
     * 
     * @var KeyInterface[]
     */
    protected $keys = array();

    /**
     * List of direct parent tables
     * 
     * @var string[]
     */
    protected $parentTables = array();

    /**
     * List of child tables
     * 
     * @var string[]
     */
    protected $childTables = array();

    /**
     * Primary key column associated with table
     * 
     * @var Column|Column[]|bool|null
     */
    protected $primaryKeyColumn;
    
    /**
     * Constructor of the table
     *
     * @param string $name
     * @param array[] $columns
     * @param array[] $foreignKeys
     * @param array[] $keys
     */
    public function __construct($name, array $columns = array(), array $keys = array(), array $foreignKeys = array())
    {
        $this->name = $name;
        foreach ($columns as $name => $columnData) {
            if ($columnData instanceof ColumnInterface) {
                $column = $columnData;
            } else {
                $column = $this->newColumn($columnData);
            }
            
            $this->columns[$column->getName()] = $column; 
        }
        
        foreach ($keys as $name => $keyData) {
            if ($keyData instanceof KeyInterface) {
                $key = $keyData;
            } else {
                $key = $this->newKey($keyData);
            }
            
            $this->keys[$key->getName()] = $key;
        }
        
        foreach ($foreignKeys as $name => $foreignKeyData) {
            if ($foreignKeyData instanceof ForeignKeyInterface) {
                $foreignKey = $foreignKeyData;
            } else {
                $foreignKey = $this->newForeignKey($foreignKeyData);
            }
                        
            $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return EcomDev_Fixture_Contract_Db_Schema_ForeignKeyInterface[]
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * @return EcomDev_Fixture_Contract_Db_Schema_KeyInterface[]
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Returns list of parent tables
     *
     * @return string[]
     */
    public function getParentTables()
    {
        return $this->parentTables;
    }

    /**
     * Returns list of child tables
     *
     * @return string[]
     */
    public function getChildTables()
    {
        return $this->childTables;
    }

    /**
     * Set list of child tables
     *
     * @param string[] $tables
     * @return $this
     */
    public function setChildTables($tables)
    {
        $this->childTables = $tables;
        return $this;
    }

    /**
     * Set list of parent tables
     *
     * @param string[] $tables
     * @return $this
     */
    public function setParentTables($tables)
    {
        $this->parentTables = $tables;
        return $this;
    }

    /**
     * Returns a primary for the table,
     * If no primary key exists, it will return false
     * 
     * @return EcomDev_Fixture_Contract_Db_Schema_KeyInterface|false
     */
    public function getPrimaryKey()
    {
        if (isset($this->keys[strtoupper(Key::TYPE_PRIMARY)])) {
            return $this->keys[strtoupper(Key::TYPE_PRIMARY)];
        }
        
        return false;
    }

    /**
     * Returns a column or array of columns,
     * if there is multi-primary key
     *
     * @return Column[]|Column|bool
     */
    public function getPrimaryKeyColumn()
    {
        if ($this->primaryKeyColumn === null) {
            $this->primaryKeyColumn = false;

            $primaryKeys = array();
          
            foreach ($this->getColumns() as $column) {
                if ($column->isPrimary()) {
                    $primaryKeys[$column->getName()] = $column;
                }
            }
            
            if ($primaryKeys && count($primaryKeys) === 1) {
                $this->primaryKeyColumn = current($primaryKeys);
            } elseif ($primaryKeys) {
                $this->primaryKeyColumn = $primaryKeys;
            }
        }
        
        return $this->primaryKeyColumn;
    }
    
    /**
     * Creates a new column based on column data
     * 
     * @param array $columnData
     * @return Column
     */
    public function newColumn(array $columnData)
    {
        list($name, $type, $defaultValue, $length, $scale, $options) = array(
            $columnData['COLUMN_NAME'],
            $columnData['DATA_TYPE'],
            $columnData['DEFAULT'],
            $columnData['LENGTH'],
            null,
            0
        );
        
        if (isset($columnData['SCALE'])) {
            $scale = (int)$columnData['SCALE'];
        }

        if (isset($columnData['PRECISION'])) {
            $length = (int)$columnData['PRECISION'];
        }
        
        if (isset($columnData['NULLABLE']) && $columnData['NULLABLE']) {
            $options |= Column::OPTION_NULLABLE;
        }

        if (isset($columnData['UNSIGNED']) && $columnData['UNSIGNED']) {
            $options |= Column::OPTION_UNSIGNED;
        }

        if (isset($columnData['PRIMARY']) && $columnData['PRIMARY']) {
            $options |= Column::OPTION_PRIMARY;
        }

        if (isset($columnData['IDENTITY']) && $columnData['IDENTITY']) {
            $options |= Column::OPTION_IDENTITY;
        }
        
        return new Column($name, $type, $defaultValue, $length, $scale, $options);
    }

    /**
     * Creates a new key from key data
     * 
     * @param array $keyData
     * @return Key
     */
    public function newKey(array $keyData)
    {
        list($name, $type, $columns) = array(
            $keyData['KEY_NAME'],
            $keyData['INDEX_TYPE'],
            $keyData['COLUMNS_LIST']
        );

        return new Key($name, $columns, $type);
    }

    /**
     * Creates a new foreign key from key data
     * 
     * @param array $keyData
     * @return ForeignKey
     */
    public function newForeignKey(array $keyData)
    {
        $name = $keyData['FK_NAME'];
        $columns = array($keyData['COLUMN_NAME']);
        $referenceTable = $keyData['REF_TABLE_NAME'];
        $referenceColumns = array($keyData['REF_COLUMN_NAME']);
        $onUpdate = ForeignKey::ACTION_NO_ACTION;
        $onDelete = ForeignKey::ACTION_NO_ACTION;

        if (!empty($keyData['ON_UPDATE'])) {
            $onUpdate = $keyData['ON_UPDATE'];
        }

        if (!empty($keyData['ON_DELETE'])) {
            $onDelete = $keyData['ON_DELETE'];
        }

        return new ForeignKey(
            $name, $columns, $referenceTable,
            $referenceColumns, $onUpdate, $onDelete
        );
    }
}
