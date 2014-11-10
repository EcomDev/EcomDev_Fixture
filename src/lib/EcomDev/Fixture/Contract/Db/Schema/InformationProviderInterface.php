<?php

/**
 * Interface for providing the data from database
 * 
 * 
 */
interface EcomDev_Fixture_Contract_Db_Schema_InformationProviderInterface
{
    /**
     * Constructor of the information provider
     *
     * @param Varien_Db_Adapter_Interface $adapter
     */
    public function __construct(Varien_Db_Adapter_Interface $adapter);

    /** 
     * Loads data from database
     *
     * @return $this
     */
    public function load();

    /**
     * Returns list of table names
     * 
     * @return string[]
     */
    public function getTableNames();

    /**
     * Returns list of columns
     * 
     * @param string $tableName
     * @return array[]
     */
    public function getColumns($tableName);

    /**
     * Returns list of indexes
     * 
     * @param string $tableName
     * @return array[]
     */
    public function getIndexes($tableName);

    /**
     * Returns list of foreign keys
     * 
     * @param string $tableName
     * @return array[]
     */
    public function getForeignKeys($tableName);
    
    /**
     * Resets information stored in last fetch
     * 
     * @return $this
     */
    public function reset();
}
