<?php

interface EcomDev_Fixture_Contract_Db_MapInterface
{
    /**
     * Table which is going to be matched by id matcher
     *
     * @return string
     */
    public function getTable();

    /**
     * Sets table property
     * 
     * @param string $table
     * @return $this
     */
    public function setTable($table);
    
    /**
     * Returns identifier, that was set by id resolver
     *
     * @return int
     */
    public function getValue();

    /**
     * Sets identifier, when it is known
     *
     * @param string|null $value
     * @return $this
     */
    public function setValue($value);

    /**
     * Is resolved flag, should return true if id is not equal to null
     * 
     * @return boolean
     */
    public function isResolved();

    /**
     * String representation of getId() call
     *
     * @return string
     */
    public function __toString();
}
