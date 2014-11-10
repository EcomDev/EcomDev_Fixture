<?php

use EcomDev_Fixture_Contract_Db_Schema_InformationProviderInterface as InformationProviderInterface;

/**
 * Interface for information model for database
 * 
 * 
 */
interface EcomDev_Fixture_Contract_Db_SchemaInterface
    extends EcomDev_Fixture_Contract_Utility_ResetAwareInterface
{
    /**
     * Constructor of the info object
     * 
     * @param InformationProviderInterface $informationProvider
     * @param string|null $tableInfoClass
     */
    public function __construct(InformationProviderInterface $informationProvider, $tableInfoClass = null);
    
    /**
     * Returns an instance of adapter
     * 
     * @return InformationProviderInterface
     */
    public function getInformationProvider();
    
    /**
     * Returns table info class
     * 
     * @return string
     */
    public function getTableInfoClass();

    /**
     * Sets table info class
     * 
     * @param string $className
     * @return $this
     */
    public function setTableInfoClass($className);

    /**
     * Return table information object
     * 
     * @param string $table
     * @return EcomDev_Fixture_Contract_Db_Schema_TableInterface
     */
    public function getTableInfo($table);

    /**
     * Return list of all parent table objects
     *
     * @param string $table
     * @return EcomDev_Fixture_Contract_Db_Schema_TableInterface[]
     */
    public function getTableAncestors($table);

    /**
     * Return list of all child table objects
     *
     * @param string $table
     * @return EcomDev_Fixture_Contract_Db_Schema_TableInterface[]
     */
    public function getTableDescendants($table);

    /**
     * Return list of all tables available in the database
     * 
     * Tables are ordered alphabetically 
     * 
     * @return string[]
     */
    public function getTableNames();

    /**
     * Returns all table names, sorted by relation
     * 
     * @return string[]
     */
    public function getTableNamesSortedByRelation();
}
