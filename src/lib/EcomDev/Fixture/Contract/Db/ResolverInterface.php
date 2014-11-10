<?php

use Varien_Db_Adapter_Interface as AdapterInterface; 
use EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface as ContainerInterface;
use EcomDev_Fixture_Contract_Db_Resolver_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;

interface EcomDev_Fixture_Contract_Db_ResolverInterface
    extends EcomDev_Fixture_Contract_Utility_ResetAwareInterface
{
    /**
     * Instantiates a resolver instance
     * 
     * @param AdapterInterface $adapter
     * @param SchemaInterface $schema
     * @param ContainerInterface|null $container
     */
    public function __construct(AdapterInterface $adapter, 
                                SchemaInterface $schema, 
                                ContainerInterface $container = null);

    /**
     * @param $typeOrTable
     * @param array|string $condition
     * @return MapInterface
     */
    public function map($typeOrTable, $condition);

    /**
     * @param $typeOrTable
     * @param array $row
     * @return MapInterface
     */
    public function mapRow($typeOrTable, $row);

    /**
     * Returns true if it is allowed to map rows for this table
     *
     * @param string $table
     * @return boolean
     */
    public function canMapRow($table);

    /**
     * Resolves mapped ids for a table
     * 
     * @param string $table
     * @return MapInterface[]
     */
    public function resolve($table);

    /**
     * Returns a container instance used by resolver
     * 
     * @return ContainerInterface
     */
    public function getContainer();

    /**
     * Returns an adapter instance used by resolver
     * 
     * @return AdapterInterface
     */
    public function getAdapter();
}
