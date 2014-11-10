<?php

/**
 * Interface of foreign key info object 
 * 
 */
interface EcomDev_Fixture_Contract_Db_Schema_ForeignKeyInterface
{
    const ACTION_CASCADE = Varien_Db_Adapter_Interface::FK_ACTION_CASCADE;
    const ACTION_NO_ACTION = Varien_Db_Adapter_Interface::FK_ACTION_NO_ACTION;
    const ACTION_SET_NULL = Varien_Db_Adapter_Interface::FK_ACTION_SET_NULL;
    const ACTION_SET_DEFAULT = Varien_Db_Adapter_Interface::FK_ACTION_SET_DEFAULT;
    const ACTION_RESTRICT = Varien_Db_Adapter_Interface::FK_ACTION_RESTRICT;

    /**
     * Constructor of the table info object
     * 
     * @param string $name
     * @param array $columns
     * @param string $referenceTable
     * @param array $referenceColumns
     * @param string $updateAction
     * @param string $deleteAction
     */
    public function __construct($name, array $columns, $referenceTable, 
                                array $referenceColumns, $updateAction = self::ACTION_CASCADE, 
                                $deleteAction = self::ACTION_CASCADE);
    
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
     * Return list of the columns
     *
     * @return string[]
     */
    public function getReferenceColumns();

    /**
     * Return list of the columns
     *
     * @return string[]
     */
    public function getReferenceTable();

    /**
     * Return the type of update
     *
     * @return string
     */
    public function getUpdateAction();
    
    /**
     * Return the type of update
     *
     * @return string
     */
    public function getDeleteAction();
}
