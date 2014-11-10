<?php

/**
 * Interface of the table info object
 * 
 */
interface EcomDev_Fixture_Contract_Db_Schema_TableInterface
{
    /**
     * Constructor of the table
     * 
     * @param string $name
     * @param array[] $columns
     * @param array[] $foreignKeys
     * @param array[] $keys
     */
    public function __construct($name, array $columns = array(), array $foreignKeys = array(), array $keys = array());
    
    /**
     * @return string
     */
    public function getName();

    /**
     * @return EcomDev_Fixture_Contract_Db_Schema_ColumnInterface[]
     */
    public function getColumns();

    /**
     * @return EcomDev_Fixture_Contract_Db_Schema_ForeignKeyInterface[]
     */
    public function getForeignKeys();

    /**
     * @return EcomDev_Fixture_Contract_Db_Schema_KeyInterface[]
     */
    public function getKeys();

    /**
     * Returns list of parent tables
     * 
     * @return string[]
     */
    public function getParentTables();

    /**
     * Returns list of child tables
     * 
     * @return string[]
     */
    public function getChildTables();

    /**
     * Set list of child tables
     *
     * @param string[] $tables
     * @return $this
     */
    public function setChildTables($tables);

    /**
     * Set list of parent tables
     *
     * @param string[] $tables
     * @return $this
     */
    public function setParentTables($tables);

    /**
     * Returns primary key for a table
     * 
     * @return EcomDev_Fixture_Contract_Db_Schema_KeyInterface|false
     */
    public function getPrimaryKey();

    /**
     * Returns a column or array of columns, 
     * if there is multi-primary key
     * 
     * @return EcomDev_Fixture_Contract_Db_Schema_ColumnInterface[]|EcomDev_Fixture_Contract_Db_Schema_ColumnInterface|bool
     */
    public function getPrimaryKeyColumn();
}
