<?php

use EcomDev_Fixture_Db_Resolver_Map as Map;

class EcomDev_FixtureTest_Test_Lib_Db_Resolver_MapTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testItHasRequiredAttributes()
    {
        $this->assertTrue(class_exists('EcomDev_Fixture_Db_Resolver_Map'));
        $this->assertClassHasAttribute('table', 'EcomDev_Fixture_Db_Resolver_Map');
        $this->assertClassHasAttribute('condition', 'EcomDev_Fixture_Db_Resolver_Map');
        $this->assertClassHasAttribute('value', 'EcomDev_Fixture_Db_Resolver_Map');
    }
    
    public function testItSetsPropertiesFromConstructorArguments()
    {
        $map = new Map('table1', array('field1' => 'value1'));
        $this->assertAttributeEquals('table1', 'table', $map);
        $this->assertAttributeEquals(array('field1' => 'value1'), 'condition', $map);
    }
    
    public function testItSortsConditionsByAlphabet()
    {
        $map = new Map('table1', array(
            'a_column' => 'value1', 
            'z_column' => 'value2', 
            'b_column' => 'value3'
        ));
        
        $this->assertAttributeSame(
            array(
                'a_column' => 'value1',
                'b_column' => 'value3',
                'z_column' => 'value2'
            ),
            'condition',
            $map
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Condition cannot be empty
     */
    public function testItRisesAnExceptionIfConditionIsEmpty()
    {
        new Map('table1', array());
    }
    
    public function testItReturnsSingleFieldForOneItemCondition()
    {
        $map = new Map('table1', array('field1' => 'value1'));
        $this->assertEquals('field1', $map->getConditionField());
        $this->assertEquals('value1', $map->getConditionValue());
    }

    public function testItReturnsArrayFieldsAndValuesForMultiItemCondition()
    {
        $map = new Map('table1', array('field1' => 'value1', 'field2' => 'value2'));
        $this->assertEquals(array('field1', 'field2'), $map->getConditionField());
        $this->assertEquals(array('value1', 'value2'), $map->getConditionValue());
    }
}