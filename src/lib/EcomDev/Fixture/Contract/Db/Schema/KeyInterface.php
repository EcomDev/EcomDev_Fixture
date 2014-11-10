<?php

/**
 * Interface of the key info object
 */
interface EcomDev_Fixture_Contract_Db_Schema_KeyInterface
{
    const TYPE_INDEX = Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX;
    const TYPE_FULLTEXT = Varien_Db_Adapter_Interface::INDEX_TYPE_FULLTEXT;
    const TYPE_UNIQUE = Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE;
    const TYPE_PRIMARY = Varien_Db_Adapter_Interface::INDEX_TYPE_PRIMARY;

    /**
     * Constructor of the key object
     * 
     * @param string $name
     * @param array $columns
     * @param string $type
     */
    public function __construct($name, array $columns = array(), $type = self::TYPE_INDEX);
    
    /**
     * Return the name of the key
     * 
     * @return string
     */
    public function getName();

    /**
     * Return list of the columns
     * 
     * @return string[]
     */
    public function getColumns();

    /**
     * Return list of types
     * 
     * @return string
     */
    public function getType();
}
