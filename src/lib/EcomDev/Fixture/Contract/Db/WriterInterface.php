<?php

use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use Varien_Db_Adapter_Interface as AdapterInterface;
use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_Writer_ErrorInterface as ErrorInterface;
use EcomDev_Fixture_Contract_Db_Writer_ContainerInterface as ContainerInterface;

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
     * 
     * @var string
     */
    const VALUE_JSON = 'json';

    /**
     * Default batch size for multi-insert operations
     * 
     * @ver int
     */
    const DEFAULT_BATCH_SIZE = 500;
    
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
        ResolverInterface $resolver = null,
        ContainerInterface $container = null
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
     * Returns container
     * 
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * Sets container instance for a writer
     * 
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container);
    
    /**
     * Schedules an insert operation
     *
     * @param string $table
     * @param array $data
     * @param int $queue
     * @return $this
     */
    public function scheduleInsert($table, $data, $queue = ContainerInterface::QUEUE_PRIMARY);

    /**
     * Schedules an update operation
     *
     * @param $table
     * @param array $data
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleUpdate($table, $data, $condition = array(), $queue = ContainerInterface::QUEUE_PRIMARY);

    /**
     * Schedules a delete operation
     *
     * @param string $table
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleDelete($table, $condition = array(), $queue = ContainerInterface::QUEUE_PRIMARY);

    /**
     * Sets batch size for mass operations
     * 
     * @param int $batchSize
     * @return $this
     */
    public function setBatchSize($batchSize);

    /**
     * Retrieves batch size for mass operations
     *
     * @return int
     */
    public function getBatchSize();
    
    /**
     * Flushes scheduled items into database
     * 
     * @return $this
     */
    public function flush();

    /**
     * Returns array with statistics of the writer process
     * 
     * @return array
     */
    public function getStats();

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
