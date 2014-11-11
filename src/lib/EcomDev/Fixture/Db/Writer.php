<?php

use Varien_Db_Adapter_Interface as AdapterInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_Schema_TableInterface as TableInterface;
use EcomDev_Fixture_Contract_Db_Schema_ColumnInterface as ColumnInterface;
use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_Map_StaticInterface as StaticMapInterface;
use EcomDev_Fixture_Contract_Db_Resolver_MapInterface as ResolvableMapInterface;
use EcomDev_Fixture_Contract_Db_Writer_ErrorInterface as ErrorInterface;
use EcomDev_Fixture_Contract_Db_Writer_ContainerInterface as ContainerInterface;

use EcomDev_Fixture_Db_Resolver as Resolver;
use EcomDev_Fixture_Db_Writer_Container as Container;

class EcomDev_Fixture_Db_Writer 
    implements EcomDev_Fixture_Contract_Db_WriterInterface
{    
    /**
     * Database adapter
     *
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Schema object
     *
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Resolver object
     *
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * Container object
     * 
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Constructor with dependencies passed
     *
     * @param AdapterInterface $adapter
     * @param SchemaInterface $schema
     * @param ResolverInterface $resolver
     */
    public function __construct(AdapterInterface $adapter, SchemaInterface $schema, 
                                ResolverInterface $resolver = null, ContainerInterface $container = null)
    {
        if ($resolver === null) {
            $resolver = new Resolver($adapter, $schema);
        }
        
        if ($container === null) {
            $container = new Container($schema, $resolver);
        }
        
        $this->adapter = $adapter;
        $this->schema = $schema;
        $this->resolver = $resolver;
        $this->container = $container;
    }


    /**
     * Returns a database adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Returns a database schema
     *
     * @return SchemaInterface
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Returns resolver
     *
     * @return ResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }
    
    /**
     * Flushes scheduled items into database
     *
     * @return $this
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * Checks if there were any errors during flush operation
     *
     * @return bool
     */
    public function hasErrors()
    {
        // TODO: Implement hasErrors() method.
    }

    /**
     * Returns all flush errors
     *
     * @return ErrorInterface[]
     */
    public function getErrors()
    {
        // TODO: Implement getErrors() method.
    }

    /**
     * Returns container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets container instance for a writer
     *
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Schedules an insert operation
     *
     * @param string $table
     * @param array $data
     * @param int $queue
     * @return $this
     */
    public function scheduleInsert($table, $data, $queue = ContainerInterface::QUEUE_PRIMARY)
    {
        $this->container->scheduleInsert($table, $data, $queue);
        return $this;
    }

    /**
     * Schedules an update operation
     *
     * @param $table
     * @param array $data
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleUpdate($table, $data, $condition = array(), $queue = ContainerInterface::QUEUE_PRIMARY)
    {
        $this->container->scheduleUpdate($table, $data, $condition, $queue);
        return $this;
    }

    /**
     * Schedules a delete operation
     *
     * @param string $table
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleDelete($table, $condition = array(), $queue = ContainerInterface::QUEUE_PRIMARY)
    {
        $this->container->scheduleDelete($table, $condition, $queue);
        return $this;
    }
}
