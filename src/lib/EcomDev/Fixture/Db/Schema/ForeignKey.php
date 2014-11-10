<?php

class EcomDev_Fixture_Db_Schema_ForeignKey implements EcomDev_Fixture_Contract_Db_Schema_ForeignKeyInterface
{
    protected $name;
    protected $columns;
    protected $referenceTable;
    protected $referenceColumns;
    protected $updateAction;
    protected $deleteAction;
    
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
                                $deleteAction = self::ACTION_CASCADE)
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->referenceTable = $referenceTable;
        $this->referenceColumns = $referenceColumns;
        $this->updateAction = $updateAction;
        $this->deleteAction = $deleteAction;
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
     * Return list of the columns
     *
     * @return string[]
     */
    public function getReferenceColumns()
    {
        return $this->referenceColumns;
    }

    /**
     * Return list of the columns
     *
     * @return string[]
     */
    public function getReferenceTable()
    {
        return $this->referenceTable;
    }

    /**
     * Return the type of update
     *
     * @return string
     */
    public function getUpdateAction()
    {
        return $this->updateAction;
    }

    /**
     * Return the type of update
     *
     * @return string
     */
    public function getDeleteAction()
    {
        return $this->deleteAction;
    }
    
}
