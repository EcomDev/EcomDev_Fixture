<?php

use EcomDev_Fixture_Db_Map_Static as StaticMap;

/**
 * Test case for a composite mapper
 * 
 * 
 */
class EcomDev_FixtureTest_Test_Lib_Db_Map_StaticTest
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testPassesTableArgumentIntoParentClass()
    {
        $map = new StaticMap('table1');
        $this->assertEquals('table1', $map->getTable());
    }
}
