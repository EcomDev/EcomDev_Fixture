<?php

interface EcomDev_Fixture_Contract_Db_Map_StaticInterface
    extends EcomDev_Fixture_Contract_Db_MapInterface
{
    /**
     * Sets a table to internal property
     * 
     * To correctly return a static map
     * 
     * @param string $table
     */
    public function __construct($table);
}
