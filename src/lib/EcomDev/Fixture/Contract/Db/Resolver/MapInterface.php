<?php

/**
 * Interface for map that can be automatically resolved
 * 
 * This one can be added into container
 */
interface EcomDev_Fixture_Contract_Db_Resolver_MapInterface
    extends EcomDev_Fixture_Contract_Db_MapInterface
{
    /**
     * Constructs a mapper for table with specified conditions
     *
     * @param string $table
     * @param array $condition condition associative array that will contain field => value mapping
     */
    public function __construct($table, array $condition);

    /**
     * Condition value that is going to be returned by
     *
     * If array is returned you should make concatenation expression in database
     *
     * @return string|array
     */
    public function getConditionValue();

    /**
     * Condition field that will be used in select expression
     *
     * If array is returned you should make concatenation expression in database
     *
     * @return string|array
     */
    public function getConditionField();
}
