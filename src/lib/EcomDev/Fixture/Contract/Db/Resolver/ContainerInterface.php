<?php

use EcomDev_Fixture_Contract_Db_Resolver_MapInterface as ResolvableMapInterface;

/**
 * Map container for generation of the resolver maps 
 *
 */
interface EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface
{

    /**
     * Sets class for creation of new map instances
     * 
     * @param string $className
     * @return $this
     * @throws InvalidArgumentException if class does not implement a resolvable map interface
     */
    public function setMapClass($className);

    /**
     * Creates a new map object based on specified arguments
     *
     * @param string $typeOrTable
     * @param string|array $condition
     * @return ResolvableMapInterface
     */
    public function map($typeOrTable, $condition);

    /**
     * Creates a map instance for row, based on id field, or if it is not found, based on default field settings
     *
     * @param $table
     * @param array $row
     * @return ResolvableMapInterface
     * @throws \RuntimeException if it is not possible to map it
     */
    public function mapRow($table, $row);

    /**
     * Returns true if it is allowed to map rows for this table
     *
     * @param string $table
     * @return boolean
     */
    public function canMapRow($table);

    /**
     * Allows mapping by row
     *
     * If $conditionFields is empty,
     * it will try to use default field condition property for table
     *
     * Unless default field or condition fields are set,
     * it is not possible use mapRow method
     *
     * If $conditionFields is an associative array it will use its pair value as default for mapping
     *
     * @param string $table
     * @param array|null $conditionFields
     * @return $this
     */
    public function mapRowRule($table, array $conditionFields = array());

    /**
     * Sets a default condition field for a table
     *
     * @param string $table
     * @param string $defaultField
     * @return $this
     */
    public function setDefaultConditionField($table, $defaultField);

    /**
     * Returns default condition field
     *
     * @param string $table
     * @return string
     */
    public function getDefaultConditionField($table);

    /**
     * Adds an alias for a table
     *
     * @param string $type
     * @param string $tableName
     * @return $this
     */
    public function alias($type, $tableName);

    /**
     * Returns all unresolved maps
     *
     * If $table parameter is specified, it is filtered by table
     *
     * @param string|null $table
     * @return ResolvableMapInterface[]
     */
    public function unresolved($table = null);

    /**
     * Returns all maps
     *
     * If $table parameter is specified, it is filtered by table name
     *
     * @param string|null $table
     * @return ResolvableMapInterface[]
     */
    public function all($table = null);

    /**
     * Resets container data
     *
     * @return $this
     */
    public function reset();
}
