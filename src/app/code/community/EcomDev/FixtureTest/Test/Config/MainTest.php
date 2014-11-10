<?php

use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

/**
 * @module EcomDev_Fixture
 */
class EcomDev_FixtureTest_Test_Config_MainTest extends EcomDev_PHPUnit_Test_Case_Config
{   
    public function testItHasModelAliases()
    {
        $this->assertModelAlias('ecomdev_fixture/processor', 'EcomDev_Fixture_Model_Processor');
        $this->assertModelAlias('ecomdev_fixture/loader', 'EcomDev_Fixture_Model_Loader');
        $this->assertModelAlias('ecomdev_fixture/mapper', 'EcomDev_Fixture_Model_Mapper');
    }
    
    public function testItHasResourceModelAlias()
    {
        $this->assertResourceModelAlias('ecomdev_fixture/loader', 'EcomDev_Fixture_Model_Resource_Loader');
    }
    
    
}
