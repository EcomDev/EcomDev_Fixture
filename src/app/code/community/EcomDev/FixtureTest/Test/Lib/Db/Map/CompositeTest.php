<?php

use EcomDev_Fixture_Db_Map_Composite as CompositeMap;

/**
 * Test case for a composite mapper
 * 
 * 
 */
class EcomDev_FixtureTest_Test_Lib_Db_Map_CompositeTest
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testItHasRequiredAttributes()
    {
        $map = new CompositeMap();
        $this->assertObjectHasAttribute('maps', $map);   
        $this->assertObjectHasAttribute('separator', $map);
    }
    
    public function testItIsInstantiatedWithDefaultArgumentValues()
    {
        $map = new CompositeMap('table1');
        $this->assertAttributeSame(array(), 'maps', $map);
        $this->assertAttributeSame('table1', 'table', $map);
        $this->assertAttributeSame('', 'separator', $map);
    }
    
    public function testItAddsMapIntoCompositeList()
    {
        $map = new CompositeMap();
        $maps = array();
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        
        foreach ($maps as $childMap) {
            $map->addMap($childMap);
        }
        
        $this->assertAttributeSame($maps, 'maps', $map);
    }
    
    public function testItIsPossibleToSetASeparator()
    {
        $map = new CompositeMap();
        $map->setSeparator('|');
        $this->assertAttributeEquals('|', 'separator', $map);
    }
    
    public function testItReturnsAddedMaps()
    {
        $map = new CompositeMap();
        $maps = array();
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');

        foreach ($maps as $childMap) {
            $map->addMap($childMap);
        }

        $this->assertSame($maps, $map->getMaps());
    }

    public function testItUsesAddedMapsForCheckingResolvedItems()
    {
        $map = new CompositeMap();
        $maps = array();
        /** @var $maps EcomDev_Fixture_Contract_Db_MapInterface[]|PHPUnit_Framework_MockObject_MockObject[] */
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');

        $maps[0]->expects($this->exactly(2))
            ->method('isResolved')
            ->willReturnOnConsecutiveCalls(true, true);
        
        $maps[1]->expects($this->exactly(2))
            ->method('isResolved')
            ->willReturnOnConsecutiveCalls(false, true);
        
        foreach ($maps as $childMap) {
            $map->addMap($childMap);
        }
        
        $this->assertFalse($map->isResolved());
        $this->assertTrue($map->isResolved());
    }
    
    public function testItConcatenatesValuesFromAddedMaps()
    {
        $map = new CompositeMap();
        $maps = array();
        /** @var $maps EcomDev_Fixture_Contract_Db_MapInterface[]|PHPUnit_Framework_MockObject_MockObject[] */
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $maps[] = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');

        $maps[0]->expects($this->exactly(2))
            ->method('isResolved')
            ->willReturnOnConsecutiveCalls(true, true);

        $maps[1]->expects($this->exactly(2))
            ->method('isResolved')
            ->willReturnOnConsecutiveCalls(false, true);

        $maps[0]->expects($this->exactly(1))
            ->method('getValue')
            ->willReturn('value1');

        $maps[1]->expects($this->exactly(1))
            ->method('getValue')
            ->willReturn('value2');
        
        foreach ($maps as $childMap) {
            $map->addMap($childMap);
        }
        
        $map->setSeparator('|');
        
        $this->assertNull($map->getValue());
        $this->assertAttributeEquals(null, 'value', $map);
        
        $this->assertSame('value1|value2', $map->getValue(), 'First time it sets value into cache');
        $this->assertAttributeEquals('value1|value2', 'value', $map);

        $this->assertSame('value1|value2', $map->getValue(), 'Second time it retrieves cached value');
    }
}
