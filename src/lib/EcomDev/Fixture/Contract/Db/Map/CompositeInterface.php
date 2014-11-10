<?php

use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;
/**
 * A map that can contains multiple maps inside
 * 
 * Resolved only when all the dependencies are resolved as well
 */
interface EcomDev_Fixture_Contract_Db_Map_CompositeInterface
    extends EcomDev_Fixture_Contract_Db_MapInterface
{
    /**
     * Instantiates a new instance of map 
     * 
     * @param string|null $table
     */
    public function __construct($table = null);

    /**
     * Add another map into composite list 
     * 
     * @param EcomDev_Fixture_Contract_Db_MapInterface $map
     * @return $this
     */
    public function addMap(EcomDev_Fixture_Contract_Db_MapInterface $map);

    /**
     * Should return all the child assigned maps
     * 
     * @return MapInterface[]
     */
    public function getMaps();

    /**
     * Sets separator to produce correct getValue() output
     * 
     * @param string $separator
     * @return $this
     */
    public function setSeparator($separator);
}
