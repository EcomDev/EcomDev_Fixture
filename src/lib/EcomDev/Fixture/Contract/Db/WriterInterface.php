<?php

use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use Varien_Db_Adapter_Interface as AdapterInterface;
use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_Writer_ErrorInterface as ErrorInterface;

/**
 * Writer interface for fixture write operations
 * 
 */
interface EcomDev_Fixture_Contract_Db_WriterInterface
{
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
     * Constructor with dependencies passed
     * 
     * @param AdapterInterface $adapter
     * @param SchemaInterface $schema
     * @param ResolverInterface $resolver
     */
    public function __construct(
        AdapterInterface $adapter, 
        SchemaInterface $schema, 
        ResolverInterface $resolver = null
    );

    /**
     * Returns a database adapter
     * 
     * @return AdapterInterface
     */
    public function getAdapter();

    /**
     * Returns a database schema
     * 
     * @return SchemaInterface
     */
    public function getSchema();

    /**
     * Returns resolver
     * 
     * @return ResolverInterface
     */
    public function getResolver();

    /**
     * Schedules an insert into the table
     * 
     * This method should handle duplicated entries with existing database records 
     * 
     * @param string $table
     * @param array $row
     * @return $this
     */
    public function schedule($table, array $row);
    
    /**
     * Flushes scheduled items into database
     * 
     * @return $this
     */
    public function flush();

    /**
     * Checks if there were any errors during flush operation
     * 
     * @return bool
     */
    public function hasErrors();

    /**
     * Returns all flush errors
     * 
     * @return ErrorInterface[]
     */
    public function getErrors();
}
