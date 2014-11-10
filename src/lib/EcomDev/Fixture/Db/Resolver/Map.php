<?php

/**
 * A resolver map implementation
 * 
 * Used to retrieve map values from the database
 */
class EcomDev_Fixture_Db_Resolver_Map
    extends EcomDev_Fixture_Db_AbstractMap
    implements EcomDev_Fixture_Contract_Db_Resolver_MapInterface, 
               EcomDev_Fixture_Contract_Utility_NotifierAwareInterface
{
    /**
     * A condition for a table
     * 
     * @var array
     */
    protected $condition;

     /**
     * Constructs a mapper for table with specified conditions
     *
     * @param string $table
     * @param array $condition condition associative array that will contain field => value mapping
     * @throws InvalidArgumentException if condition is empty
     */
    public function __construct($table, array $condition)
    {
        parent::__construct($table);
        
        if (empty($condition)) {
            throw new InvalidArgumentException('Condition cannot be empty');
        }
        
        ksort($condition);
        $this->condition = $condition;
    }

    /**
     * Condition value that is going to be used for filtering
     *
     * @return string[]|string
     */
    public function getConditionValue()
    {
        if (count($this->condition) === 1) {
            return current($this->condition);
        }
        
        return array_values($this->condition);
    }

    /**
     * Condition field that will be used in select expression
     *
     * @return string[]|string
     */
    public function getConditionField()
    {
        if (count($this->condition) === 1) {
            return key($this->condition);
        }
        
        return array_keys($this->condition);
    }
}