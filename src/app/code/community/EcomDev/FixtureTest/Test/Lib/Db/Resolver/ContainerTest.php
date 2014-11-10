<?php
use EcomDev_Fixture_Db_Resolver_Container as Container;

class EcomDev_FixtureTest_Test_Lib_Db_Resolver_ContainerTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * @var Container
     */
    protected $container;
    
    protected function setUp()
    {
        $this->container = new Container(); 
    }
    
    public function testItUsesHashMapForStoringMapItems()
    {
        $this->assertObjectHasAttribute('map', $this->container);
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Utility_HashMap', 'map', $this->container);
    }
    
    public function testItHasAttributeForDefaultConditionField()
    {
        $this->assertObjectHasAttribute('defaultConditionField', $this->container);
        $this->assertAttributeInternalType('array', 'defaultConditionField', $this->container);
    }

    public function testItHasAttributeForRowMap()
    {
        $this->assertObjectHasAttribute('mapRowRule', $this->container);
        $this->assertAttributeInternalType('array', 'mapRowRule', $this->container);
    }
    
    public function testItIsPossibleToManipulateWithDefaultConditionField()
    {
        $this->assertAttributeEmpty('defaultConditionField', $this->container);
        $this->container->setDefaultConditionField('table1', 'field1');
        $this->assertAttributeNotEmpty('defaultConditionField', $this->container);
        $this->assertSame('field1', $this->container->getDefaultConditionField('table1'));        
        $this->assertFalse($this->container->getDefaultConditionField('table2'), 'Non set value should return false');        
    }
    
    
    public function testItIsPossibleToMapATableWithoutFields()
    {
        $this->container->mapRowRule('table1');
        $this->assertAttributeEquals(array('table1' => array()), 'mapRowRule', $this->container);
    }
    
    public function testItIsPossibleToMapATableWithNumericFieldList()
    {
        $this->container->mapRowRule('table1', array('field1', 'field2', 'field3'));
        $this->assertAttributeEquals(
            array(
                'table1' => array(
                    'field1' => null, 
                    'field2' => null, 
                    'field3' => null
                )
            ), 
            'mapRowRule', 
            $this->container
        );
    }
    
    public function testItIsPossibleToMapATableWithFieldAndDefaultValue()
    {
        $this->container->mapRowRule('table1', array('field1', 'field2' => 'value1', 'field3'));
        $this->assertAttributeEquals(
            array(
                'table1' => array(
                    'field1' => null,
                    'field2' => 'value1',
                    'field3' => null
                )
            ),
            'mapRowRule',
            $this->container
        );
    }
    
    public function testItChecksMapRowAvailability()
    {
        $this->container->mapRowRule('table1');
        $this->container->setDefaultConditionField('table1', 'field1');
        
        $this->container->mapRowRule('table2', array('field1'));
        $this->container->mapRowRule('table3');
                
        // Only if conditions or default value is specified
        $this->assertTrue($this->container->canMapRow('table1'));
        $this->assertTrue($this->container->canMapRow('table2'));
        // Otherwise, even if mapping allowed it should return false
        $this->assertFalse($this->container->canMapRow('table3'));
        $this->assertFalse($this->container->canMapRow('table4'));
    }

    public function testItHasMapClassOptionWithDefaultValue()
    {
        $this->assertObjectHasAttribute('mapClass', $this->container);
        $this->assertAttributeEquals('EcomDev_Fixture_Db_Resolver_Map', 'mapClass', $this->container);
    }
    
    public function testItIsPossibleToChangeClassForMapInstance()
    {
        $mock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Resolver_MapInterface');
        $this->container->setMapClass(get_class($mock));
        $this->assertAttributeEquals(get_class($mock), 'mapClass', $this->container);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage stdClass class should implement EcomDev_Fixture_Contract_Db_Resolver_MapInterface interface
     */
    public function testItRisesAnExceptionIfPassedClassDoesNotImplementMapInterface()
    {
        $this->container->setMapClass('stdClass');
    }
    
    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage There is no mapping data or default condition field is missing for "table1"
     */
    public function testItRisesAnExceptionWhenMapRowIsCalled()
    {
        $this->container->mapRow('table1', array('field1' => 'field2'));
    }

    public function testItCreatesANewMapItem()
    {
        $map = $this->container->map('table1', array('field1' => 'value1'));
        $this->assertInstanceOf('EcomDev_Fixture_Db_Resolver_Map', $map);
        $this->assertEquals('table1', $map->getTable());
        $this->assertEquals('field1', $map->getConditionField());
        $this->assertEquals('value1', $map->getConditionValue());
    }

    public function testItStoresEveryMapItemInHashMap()
    {
        $map = $this->container->map('table1', array('field1' => 'value1'));
        $mapStorage = $this->readAttribute($this->container, 'map');
        $this->assertSame(
            $map,
            $mapStorage[
                array(
                    'table' => 'table1', 
                    'condition' => array(
                        'field1' => 'value1'
                    )
                )
            ]
        );
    }
    
    public function testItCreatesAMapItemOnlyOnce()
    {
        $map1 = $this->container->map('table1', array('field1' => 'value1'));
        $map2 = $this->container->map('table1', array('field1' => 'value1'));
        
        $this->assertSame($map1, $map2);
    }
    
    public function testItIsPossibleToUseAliasInsteadOfTable()
    {
        $this->container->alias('entity', 'table1');
        
        $map1 = $this->container->map('entity', array('field1' => 'value1'));
        $map2 = $this->container->map('table1', array('field1' => 'value1'));

        $this->assertSame($map1, $map2);
    }
    
    public function testItIsPossibleToUseDefaultConditionField()
    {
        $this->container->setDefaultConditionField('table1', 'field1');
        $map1 = $this->container->map('table1', 'value1');
        $map2 = $this->container->map('table1', array('field1' => 'value1'));

        $this->assertSame($map1, $map2);
    }
    
    public function testItIsPossibleToAliasTable()
    {
        $this->container->alias('entity', 'table1');
        $this->assertAttributeEquals(
            array(
                'entity' => 'table1'
            ),
            'alias',
            $this->container
        );
    }
    
    public function testItCreatesMappedItemFromRowWithDefaultConditionField()
    {
        $this->container->mapRowRule('table1');
        $this->container->setDefaultConditionField('table1', 'field1');
        
        $map = $this->container->mapRow('table1', array('field1' => 'value1', 'field2' => 'value2'));
        $this->assertInstanceOf('EcomDev_Fixture_Db_Resolver_Map', $map);
        
        $this->assertEquals('table1', $map->getTable());
        $this->assertEquals('field1', $map->getConditionField());
        $this->assertEquals('value1', $map->getConditionValue());
    }
    
    public function testItCreatesMappedItemFromRowConditionRulesAndSetsDefaultValueIfNull()
    {
        $this->container->mapRowRule('table1', array('field1' => 'defaultValue1', 'field2' => 'defaultValue2'));
        
        $map = $this->container->mapRow('table1', array('field1' => '', 'field2' => null));

        $this->assertEquals('table1', $map->getTable());
        $this->assertEquals(array('field1', 'field2'), $map->getConditionField());
        $this->assertEquals(array('', 'defaultValue2'), $map->getConditionValue());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Value for "field2" in "table1" is missing and no default value provided
     */
    public function testItThrowsAnErrorIfRequiredFieldIsMissingForMapRowMethod()
    {
        $this->container->mapRowRule('table1', array('field1' => 'defaultValue1', 'field2'));
        
        $this->container->mapRow('table1', array('field1' => 'value1', 'field2' => null));
    }
    
    public function testItReturnsAllItemsByTable()
    {
        $expectedResult = array();

        $expectedResult[] = $this->container->map('table1', array('field1' => 'value1'));
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value2'));
        $this->container->map('table2', array('field1' => 'value1'));
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value3'));
        
        $this->assertSame($expectedResult, $this->container->all('table1'));
    }
    
    public function testItReturnsAllItems()
    {
        $expectedResult = array();

        $expectedResult[] = $this->container->map('table1', array('field1' => 'value1'));
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value2'));
        $expectedResult[] = $this->container->map('table2', array('field1' => 'value1'));
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value3'));

        $this->assertSame($expectedResult, $this->container->all());
    }

    public function testItReturnsUnresolvedItemsByTable()
    {
        $expectedResult = array();
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value1'));
        // This item is resolved
        $this->container->map('table1', array('field1' => 'value2'))->setValue('item2');
        // This item is from another table
        $this->container->map('table2', array('field1' => 'value1'));
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value3'));

        $this->assertSame($expectedResult, $this->container->unresolved('table1'));
    }

    public function testItReturnsUnresolvedItems()
    {
        $expectedResult = array();

        $expectedResult[] = $this->container->map('table1', array('field1' => 'value1'));
        $this->container->map('table1', array('field1' => 'value2'))->setValue('item2');
        $expectedResult[] = $this->container->map('table2', array('field1' => 'value1'));
        $expectedResult[] = $this->container->map('table1', array('field1' => 'value3'));

        $this->assertSame($expectedResult, $this->container->unresolved());
    }
    
    public function testItResetsHashMapOnly()
    {
        $this->container->alias('one_table', 'table1');
        $this->container->map('one_table', array('field1' => 'value1'));
        $map = $this->readAttribute($this->container, 'map');
        
        $this->container->reset();
        
        $this->assertAttributeNotEmpty('alias', $this->container, 'Aliases should not be cleared');
        $this->assertAttributeSame($map, 'map', $this->container, 'The object should not be changed');
        $this->assertEmpty($this->container->all(), 'Map should be empty');
    }
    
    public function testItImplementsNotifierInterface()
    {
        $this->assertInstanceOf('EcomDev_Fixture_Contract_Utility_NotifierInterface', $this->container);
    }


    public function testItImplementsNotifierAwareInterface()
    {
        $this->assertInstanceOf('EcomDev_Fixture_Contract_Utility_NotifierAwareInterface', $this->container);
        $this->assertObjectHasAttribute('notifiers', $this->container);
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Utility_Notifier_Container', 'notifiers', $this->container);
    }

    public function testItIsProxiesCallsToNotifier()
    {
        $notifierMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $notifierContainerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->container, 'notifiers', $notifierContainerMock);
        $notifierContainerMock->expects($this->once())
            ->method('add')
            ->with($notifierMock)
            ->willReturnSelf();

        $this->assertSame($this->container, $this->container->addNotifier($notifierMock));

        $notifierContainerMock->expects($this->once())
            ->method('remove')
            ->willReturnSelf()
        ;

        $this->assertSame($this->container, $this->container->removeNotifier($notifierMock));

        $notifierContainerMock->expects($this->once())
            ->method('items')
            ->willReturn(array($notifierMock))
        ;

        $this->assertSame(array($notifierMock), $this->container->getNotifiers());
    }
    
    public function testItAddsItSelfAsNotifierOnMap()
    {
        $map = $this->container->map('table1', array('condition1' => 'value1'));
        $this->assertSame(array($this->container), $map->getNotifiers());
    }
    
    public function testItNotifyOthersAboutCreationOfMap()
    {
        $notifierContainerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->container, 'notifiers', $notifierContainerMock);
        
        $collectedMaps = array();
        $notifierContainerMock->expects($this->exactly(2))
            ->method('notify')
            ->with($this->isInstanceOf('EcomDev_Fixture_Db_Resolver_Map'), 'map', 'table1')
            ->willReturnCallback(function ($map) use (&$collectedMaps) {
                $collectedMaps[] = $map;
            });

        $actualMaps = array();
        $actualMaps[] = $this->container->map('table1', array('field1' => 'value2'));
        $actualMaps[] = $this->container->map('table1', array('field1' => 'value1'));
        $this->assertSame($collectedMaps, $actualMaps);
    }
    
}
