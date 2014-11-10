<?php

class EcomDev_Fixture_Db_Schema_Key implements EcomDev_Fixture_Contract_Db_Schema_KeyInterface
{
    protected $name;
    
    protected $columns;
    
    protected $type;
    
    /**
     * Constructor of the key object
     *
     * @param string $name
     * @param array $columns
     * @param string $type
     */
    public function __construct($name, array $columns = array(), $type = self::TYPE_INDEX)
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->type = $type;
    }

    /**
     * Return the name of the key
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return list of the columns
     *
     * @return string[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Return list of types
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

}