<?php

use EcomDev_Fixture_Db_Schema_Table as Table;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Utils_Reflection as ReflectionUtil;

class EcomDev_FixtureTest_Test_Lib_Db_Writer_ContainerTest
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * @var EcomDev_Fixture_Db_Writer_Container|PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var EcomDev_Fixture_Db_Schema|PHPUnit_Framework_MockObject_MockObject
     */
    private $schemaMock;

    /**
     * @var EcomDev_Fixture_Db_Resolver|PHPUnit_Framework_MockObject_MockObject
     */
    private $resolverMock;
    
    protected function setUp()
    {
        $this->resolverMock = $this->getMockBuilder('EcomDev_Fixture_Db_Resolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->schemaMock = $this->getMockBuilder('EcomDev_Fixture_Db_Schema')
            ->disableOriginalConstructor()
            ->getMock();
        
        $annotations = $this->getAnnotations();
        $methods = isset($annotations['method']['mockMethod']) ? $annotations['method']['mockMethod'] : array();
        if ($methods) {
            $this->container = $this->getMock(
                'EcomDev_Fixture_Db_Writer_Container', 
                $methods, 
                array(
                    $this->schemaMock,
                    $this->resolverMock
                )
            );
        } else {
            $this->container = new EcomDev_Fixture_Db_Writer_Container($this->schemaMock, $this->resolverMock);
        }
        
        
    }
    
    public function testItHasPropertiesToDealWithResolvedMaps()
    {
        $this->assertObjectHasAttribute('knownMaps', $this->container);
        $this->assertObjectHasAttribute('schedule', $this->container);
        $this->assertObjectHasAttribute('scheduleColumnMap', $this->container);
        $this->assertObjectHasAttribute('scheduleConditionMap', $this->container);
        $this->assertObjectHasAttribute('resolvedSchedule', $this->container);
        $this->assertObjectHasAttribute('resolvedMaps', $this->container);
        $this->assertObjectHasAttribute('unresolvedSchedulePrimaryKey', $this->container);
        $this->assertObjectHasAttribute('unresolvedScheduleColumn', $this->container);
        $this->assertObjectHasAttribute('unresolvedScheduleCondition', $this->container);
    }
    
    public function testItSetsResolverAndSchemaFromConstructor()
    {
        $this->assertAttributeSame($this->schemaMock, 'schema', $this->container);
        $this->assertAttributeSame($this->resolverMock, 'resolver', $this->container);
    }

    public function testItMapsRowIfCanMapReturnsTrueButNotOtherwise()
    {
        $this->resolverMock->expects($this->exactly(2))
            ->method('canMapRow')
            ->withConsecutive(array('table1'), array('table2'))
            ->willReturnOnConsecutiveCalls(true, false);

        $map = new EcomDev_Fixture_Db_Resolver_Map('table1', array('field' => 'value'));
        $this->resolverMock->expects($this->once())
            ->method('mapRow')
            ->with('table1', array('field' => 'value', 'field2' => 'value2'))
            ->willReturn($map);

        $this->assertSame(
            $map,
            $this->container->registerRowMap('table1', array('field' => 'value', 'field2' => 'value2'))
        );

        $this->assertEquals(
            false,
            $this->container->registerRowMap('table2', array('field' => 'value', 'field2' => 'value2'))
        );
    }
    
    /**
     * @param string|bool $recommendedValue
     * @param mixed $value
     * @param mixed $expectedValue
     * @param mixed $expectedRecommendedValue
     * @dataProvider dataProviderPrepareColumnValue
     */
    public function testItPreparesColumnValueCorrectly($value, $expectedValue, $recommendedValue = false, 
                                                       $expectedRecommendedValue = null)
    {
        $table = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Schema_TableInterface');
        $column = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Schema_ColumnInterface');
        
        $table->expects($this->any())
            ->method('getName')
            ->willReturn('test_table');
        
        $column->expects($this->any())
            ->method('getName')
            ->willReturn('column_name');
        
        if ($expectedRecommendedValue === null) {
            $expectedRecommendedValue = $value;
        }
        
        if ($recommendedValue !== false) {
            $column->expects($this->once())
                ->method('getRecommendedValue')
                ->with($expectedRecommendedValue)
                ->willReturn($recommendedValue);
        } else {
            $column->expects($this->never())
                ->method('getRecommendedValue');
        }
        
        if (is_array($expectedValue)) {
            list($exceptionClass, $exceptionMessage) = $expectedValue;
            $this->setExpectedException($exceptionClass, $exceptionMessage);
        }
        
        $actualValue = $this->container->prepareColumnValue($table, $column, $value);
        $this->assertSame($expectedValue, $actualValue);
    }

    /**
     * 
     */
    public function dataProviderPrepareColumnValue()
    {
        $map = new EcomDev_Fixture_Db_Map_Static('test_table');
        return array(
            'normal_value' => array(
                1,
                '1',
                '1'
            ),
            'map_value' => array(
                $map,
                $map
            ),
            'json_value' => array(
                array(
                    'json' => array('value' => 'key')
                ),
                json_encode(array('value' => 'key'))
            ),
            'serialize_value' => array(
                array(
                    'serialized' => array('value' => 'key')
                ),
                serialize(array('value' => 'key'))
            ),
            'unknown_value' => array(
                array(
                    'dummy' => array('value' => 'key')
                ),
                array(
                    'InvalidArgumentException', 'Invalid value supplied for "column_name" in "test_table"'
                )
            )
        );
    }
    
    public function testItCreatesAKeyFromArguments()
    {
        $this->assertEquals(
            'test/item/two',
            $this->container->implodeKey('test', 'item', 'two')
        );

        $this->assertEquals(
            'main/two',
            $this->container->implodeKey('main', 'two')
        );

        $this->assertEquals(
            'one/two/three/four/five/six',
            $this->container->implodeKey('one', 'two', 'three', 'four', 'five', 'six')
        );
    }
    
    public function testItExplodesKeyDataFromKey()
    {
        $this->assertEquals(
            array('test', 'item', 'two'),
            $this->container->explodeKey('test/item/two')
        );

        $this->assertEquals(
            array('main', 'two'),
            $this->container->explodeKey('main/two')
        );

        $this->assertEquals(
            array('one', 'two', 'three', 'four', 'five', 'six'),
            $this->container->explodeKey('one/two/three/four/five/six')
        );

        $this->assertEquals(
            array('one', 'two', 'three/four/five/six'),
            $this->container->explodeKey('one/two/three/four/five/six', 3)
        );
    }
    
    public function testItImplementsNotifierInterface()
    {
        $this->assertInstanceOf(
            'EcomDev_Fixture_Contract_Utility_NotifierInterface', 
            $this->container
        );
    }
    
    public function testItRegistersMap()
    {
        $this->assertAttributeEmpty('knownMaps', $this->container);
        $map = new EcomDev_Fixture_Db_Map_Static('table1');
        $this->container->registerMap($map);
        $this->assertContains(
            $this->container,
            $map->getNotifiers()
        );
        $this->assertAttributeEquals(
            array(
                spl_object_hash($map) => $map
            ),
            'knownMaps',
            $this->container
        );
        
        $this->assertAttributeEmpty(
            'resolvedMaps',
            $this->container
        );
    }
    
    public function testItAddsMapIntoResolvedMapsIfMapIsResolved()
    {
        $map = new EcomDev_Fixture_Db_Map_Static('table1');
        $map->setValue(1);
        $this->container->registerMap($map);
        $this->assertAttributeEquals(
            array(
                spl_object_hash($map) => $map
            ),
            'knownMaps',
            $this->container
        );

        $this->assertAttributeEquals(
            array(
                spl_object_hash($map) => spl_object_hash($map)
            ),
            'resolvedMaps',
            $this->container
        );
    }

    /**
     * @param EcomDev_Fixture_Contract_Db_MapInterface $map
     * @param Column $column
     * @param int $rowIndex
     * @param string $key
     * @param array $expectedColumnMap
     * @param array $expectedUnresolvedScheduleColumn
     * @param array $expectedUnresolvedSchedulePrimaryKey
     * @mockMethod registerMap
     * @dataProvider dataProviderRegisterColumnMap
     */
    public function testItRegistersMappedColumn(
        $map, $column, $rowIndex, $key,
        $expectedColumnMap = array(), 
        $expectedUnresolvedScheduleColumn = array(), 
        $expectedUnresolvedSchedulePrimaryKey = array()
    )
    {
        $this->container->expects($this->once())
            ->method('registerMap')
            ->with($map)
            ->willReturnSelf();
        
        $this->assertSame(
            $this->container, 
            $this->container->registerColumnMap($map, $column, $key, $rowIndex)
        );
        
        $this->assertAttributeEquals(
            $expectedColumnMap,
            'scheduleColumnMap',
            $this->container
        );
        
        $this->assertAttributeEquals(
            $expectedUnresolvedScheduleColumn,
            'unresolvedScheduleColumn',
            $this->container
        );

        $this->assertAttributeEquals(
            $expectedUnresolvedSchedulePrimaryKey,
            'unresolvedSchedulePrimaryKey',
            $this->container
        );
    }

    public function dataProviderRegisterColumnMap()
    {
        $primaryColumnAutoincrement = new Column(
            'column_primary_autoincrement', 
            Column::TYPE_INTEGER, 
            null, null, null, 
            Column::OPTION_PRIMARY | Column::OPTION_IDENTITY
        );
        
        $primaryColumn = new Column(
            'column_primary',
            Column::TYPE_INTEGER,
            null, null, null,
            Column::OPTION_PRIMARY
        );

        $regularColumn = new Column(
            'column_regular',
            Column::TYPE_INTEGER
        );
        
        $map = new EcomDev_Fixture_Db_Map_Static('table1');
        $objectId = spl_object_hash($map);
        $key = '0/insert/table1';
        $rowIndex = 0;
        
        return array(
            'column_primary_autoincrement' => array(
                $map, $primaryColumnAutoincrement, $rowIndex, $key, 
                array(
                    $objectId => array(
                        $key . '/' . $rowIndex . '/column_primary_autoincrement' => true
                    )
                ), 
                array(), 
                array(
                    $key => array(
                        $objectId => $rowIndex
                    )
                )
            ),
            'column_primary' => array(
                $map, $primaryColumn, $rowIndex, $key,
                array(
                    $objectId => array(
                        $key . '/' . $rowIndex . '/column_primary' => true
                    )
                ),
                array(
                    $key => array(
                        $rowIndex => array(
                            'column_primary' => $objectId
                        )
                    )
                )
            ),
            'column_regular' => array(
                $map, $regularColumn, $rowIndex, $key,
                array(
                    $objectId => array(
                        $key . '/' . $rowIndex . '/column_regular' => true
                    )
                ),
                array(
                    $key => array(
                        $rowIndex => array(
                            'column_regular' => $objectId
                        )
                    )
                )
            )
        );
    }

    /**
     * @param EcomDev_Fixture_Contract_Db_MapInterface $map
     * @param string $conditionName
     * @param int $rowIndex
     * @param string $key
     * @param int|null $index
     * @param array $expectedConditionMap
     * @param array $expectedUnresolvedScheduleCondition
     * @mockMethod registerMap
     * @dataProvider dataProviderRegisterConditionMap
     */
    public function testItRegistersConditionMap(
        $map, $conditionName, $rowIndex, $key, $index,
        $expectedConditionMap = array(),
        $expectedUnresolvedScheduleCondition = array()
    )
    {
        $this->container->expects($this->once())
            ->method('registerMap')
            ->with($map)
            ->willReturnSelf();

        $this->assertSame(
            $this->container,
            $this->container->registerConditionMap(
                $map, $conditionName, $key, $rowIndex, $index
            )
        );

        $this->assertAttributeEquals(
            $expectedConditionMap,
            'scheduleConditionMap',
            $this->container
        );

        $this->assertAttributeEquals(
            $expectedUnresolvedScheduleCondition,
            'unresolvedScheduleCondition',
            $this->container
        );
    }

    public function dataProviderRegisterConditionMap()
    {
        $map = new EcomDev_Fixture_Db_Map_Static('table1');
        $objectId = spl_object_hash($map);
        $key = '0/insert/table1';
        $rowIndex = 0;

        return array(
            'condition_single' => array(
                $map, 'field1', $rowIndex, $key, null,
                array(
                    $objectId => array(
                        $key . '/' . $rowIndex . '/field1' => true
                    )
                ),
                array(
                    $key => array(
                        $rowIndex => array(
                            'field1' => array(
                                $objectId => true
                            )
                        )
                    )
                )
            ),
            'condition_multiple_zero' => array(
                $map, 'field2', $rowIndex, $key, 0,
                array(
                    $objectId => array(
                        $key . '/' . $rowIndex . '/field2' => 0
                    )
                ),
                array(
                    $key => array(
                        $rowIndex => array(
                            'field2' => array(
                                $objectId => 0
                            )
                        )
                    )
                )
            ),
            'condition_multiple_one' => array(
                $map, 'field2', $rowIndex, $key, 1,
                array(
                    $objectId => array(
                        $key . '/' . $rowIndex . '/field2' => 1
                    )
                ),
                array(
                    $key => array(
                        $rowIndex => array(
                            'field2' => array(
                                $objectId => 1
                            )
                        )
                    )
                )
            )
        );
    }
    
    public function testItReceivesNotificationAboutResolvedMaps()
    {
        $mapOne = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $mapTwo = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $objectId = spl_object_hash($mapTwo);
        $this->container->registerMap($mapTwo);
        
        $this->assertSame($this->container, $this->container->notify($mapOne, 'resolve', true));
        $this->assertAttributeEmpty('resolvedMaps', $this->container);

        $this->assertSame($this->container, $this->container->notify($mapTwo, 'resolve', false));
        $this->assertAttributeEmpty('resolvedMaps', $this->container);

        $this->assertSame($this->container, $this->container->notify($mapTwo, 'resolve', true));
        $this->assertAttributeEquals(array($objectId => $objectId), 'resolvedMaps', $this->container);
    }
    
    public function testItRemovesItSelfFromMapAsNotifierOnReset()
    {
        $map = $this->getMock(
            'EcomDev_Fixture_Db_Map_Static',
            array('removeNotifier'), 
            array('table1')
        );
        
        $map->expects($this->once())
            ->method('removeNotifier')
            ->with($this->container)
            ->willReturnSelf();
        
        $this->container
            ->registerMap($map)
            ->notify($map, 'reset');
    }

    public function testItAddsRowToResolvedSchedule()
    {
        $key = '0/insert/table1';
        
        $scheduleColumn = array(
            $key =>  array(
                0 => array(
                    'column1' => 'some_hash'
                ),
                1 => array(),
                2 => array(),
                3 => array()
            )
        );
        
        $scheduleCondition = array(
            $key => array(
                0 => array(),
                1 => array(),
                2 => array(
                    'field1' => array(
                        'hash' => true
                    )
                ),
                3 => array()
            )
        );
        
        $rowsToResolve = array(
            0 => false, 
            1 => true, 
            2 => false, 
            3 => true, 
            4 => true
        );
        
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container, array(
                'unresolvedScheduleColumn' => $scheduleColumn,
                'unresolvedScheduleCondition' => $scheduleCondition
            )
        );

        $expectedResolvedKeys = array();
        
        foreach ($rowsToResolve as $rowId => $isResolved) {
            $this->assertSame($this->container, $this->container->resolveSchedule($key, $rowId));
            if ($isResolved) {
                $expectedResolvedKeys[$key][$rowId] = $rowId;
                if (isset($scheduleColumn[$key][$rowId])) {
                    unset($scheduleColumn[$key][$rowId]);
                }
                if (isset($scheduleCondition[$key][$rowId])) {
                    unset($scheduleCondition[$key][$rowId]);
                }
            }
            
            $this->assertAttributeSame(
                $expectedResolvedKeys, 
                'resolvedSchedule', 
                $this->container, 
                'Expected resolvedSchedule result for ' . $rowId . ' is not correct'
            );
            
            $this->assertAttributeSame(
                $scheduleColumn, 
                'unresolvedScheduleColumn', 
                $this->container,
                'Expected unresolvedScheduleColumn result for ' . $rowId . ' is not correct'
            );
            
            $this->assertAttributeSame(
                $scheduleCondition, 
                'unresolvedScheduleCondition', 
                $this->container,
                'Expected unresolvedScheduleCondition result for ' . $rowId . ' is not correct'
            );
        }
    }


    /**
     * @param array $properties
     * @param array $expectedResolveScheduleCalls
     * @param array $expectedSchedule
     * @param array $expectedUnresolvedColumn
     * @param array $expectedUnresolvedPrimaryKey
     * @param array $expectedUnresolvedCondition
     * @dataProvider dataProviderResolveMaps
     * @mockMethod resolveSchedule
     */
    public function testItResolvesScheduleForMap(
        array $properties,
        array $expectedResolveScheduleCalls = array(),
        array $expectedSchedule = array(),
        array $expectedUnresolvedColumn = array(),
        array $expectedUnresolvedCondition = array(),
        array $expectedUnresolvedPrimaryKey = array())
    {
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container, 
            $properties
        );
        
        if ($expectedResolveScheduleCalls) {
            $matcher = $this->container->expects(
                $this->exactly(count($expectedResolveScheduleCalls))
            );
            
            $matcher->method('resolveSchedule');
            call_user_func_array(
                array($matcher, 'withConsecutive'), 
                $expectedResolveScheduleCalls
            );
        } else {
            $this->container->expects($this->never())
                ->method('resolveSchedule');
        }
        
        $this->assertSame(
            $this->container, 
            $this->container->resolve()
        );
        
        $this->assertAttributeEmpty(
            'resolvedMaps',
            $this->container,
            'Resolved maps are not empty'
        );
        
        $this->assertAttributeEquals(
            $expectedSchedule,
            'schedule',
            $this->container,
            'Schedule is invalid'
        );
        
        $this->assertAttributeEquals(
            $expectedUnresolvedColumn,
            'unresolvedScheduleColumn',
            $this->container,
            'Unresolved schedule columns are wrong'
        );

        $this->assertAttributeEquals(
            $expectedUnresolvedCondition,
            'unresolvedScheduleCondition',
            $this->container,
            'Unresolved schedule conditions are wrong'
        );

        $this->assertAttributeEquals(
            $expectedUnresolvedPrimaryKey,
            'unresolvedSchedulePrimaryKey',
            $this->container,
            'Unresolved schedule primary keys are wrong'
        );
    }
    
    public function dataProviderResolveMaps()
    {
        $maps = array(
            'map1' => new EcomDev_Fixture_Db_Map_Static('table1'), 
            'map2' => new EcomDev_Fixture_Db_Map_Static('table1'), 
            'map3' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map4' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map5' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map6' => new EcomDev_Fixture_Db_Map_Static('table1'),
        );
        
        $maps['map1']->setValue(1);
        $maps['map2']->setValue(2);
        $maps['map3']->setValue(3);
        $resolvedMaps = array(
            'map1' => 'map1',
            'map2' => 'map2',
            'map3' => 'map3'
        );
        $schedule = array(
            '0/insert/table1' => array(
                0 => array(
                    'column1' => $maps['map1'],
                    'column2' => 'text_value',
                    'column3' => '0'
                ),
                1 => array(
                    'column1' => $maps['map2'],
                    'column2' => 'text_value',
                    'column3' => '0'
                ),
                2 => array(
                    'column1' => $maps['map3'],
                    'column2' => 'text_value',
                    'column3' => $maps['map2']
                ),
                3 => array(
                    'column1' => $maps['map4'],
                    'column2' => 'text_value',
                    'column3' => $maps['map3']
                ),
                4 => array(
                    'column1' => $maps['map5'],
                    'column2' => 'text_value',
                    'column3' => $maps['map4']
                ),
                5 => array(
                    'column1' => $maps['map6'],
                    'column2' => 'text_value',
                    'column3' => $maps['map5']
                )
            ),
            '0/update/table1' => array(
                0 => array(
                    'data' => array(
                        'column2' => 'text_value2',
                        'column3' => $maps['map1']
                    ),
                    'condition' => array(
                        'column1' => $maps['map2'],
                        'column2' => array($maps['map1'], $maps['map2'])
                    )
                )
            ),
            '0/delete/table1' => array(
                0 => array(
                    'column1' => array(
                        0 => $maps['map1'],
                        1 => $maps['map2']
                    ),
                    'column2' => $maps['map1']
                )
            )
        );
        
        $scheduleColumnMap = array(
            'map1' => array(
                '0/insert/table1/0/column1' => true,
                '0/update/table1/0/column3' => true
            ),
            'map2' => array(
                '0/insert/table1/1/column1' => true,
                '0/insert/table1/2/column3' => true,
            ),
            'map3' => array(
                '0/insert/table1/2/column1' => true,
                '0/insert/table1/3/column3' => true,
            ),
            'map4' => array(
                '0/insert/table1/3/column1' => true,
                '0/insert/table1/4/column3' => true,
            ),
            'map5' => array(
                '0/insert/table1/4/column1' => true,
                '0/insert/table1/5/column3' => true,
            ),
            'map6' => array(
                '0/insert/table1/5/column1' => true
            )
        );

        $unresolvedScheduleColumn = array(
            '0/insert/table1' => array(
                2 => array(
                    'column3' => 'map2'
                ),
                3 => array(
                    'column3' => 'map3'
                ),
                4 => array(
                    'column3' => 'map4'
                ),
                5 => array(
                    'column3' => 'map5'
                )
            ),
            '0/update/table1' => array(
                0 => array(
                    'column3' => 'map1'
                )
            )
        );

        $scheduleConditionMap = array(
            'map1' => array(
                '0/delete/table1/0/column1' => 0,
                '0/delete/table1/0/column2' => true,
                '0/update/table1/0/column2' => 0,
                '0/update/table1/0/column3' => true
            ),
            'map2' => array(
                '0/update/table1/0/column1' => true,
                '0/delete/table1/0/column1' => 1,
                '0/update/table1/0/column2' => 1
            )
        );

        $unresolvedScheduleCondition = array(
            '0/update/table1' => array(
                0 => array(
                    'column1' => array(
                        'map2' => true
                    ),
                    'column2' => array(
                        'map1' => 0,
                        'map2' => 1
                    )
                )
            ),
            '0/delete/table1' => array(
                0 => array(
                    'column1' => array(
                        'map1' => 0,
                        'map2' => 1
                    ),
                    'column2' => array(
                        'map1' => true
                    )
                )
            )
        );

        $unresolvedSchedulePrimaryKey = array(
            '0/insert/table1' => array(
                'map1' => 0,
                'map2' => 1,
                'map3' => 2,
                'map4' => 3,
                'map5' => 4,
                'map6' => 5
            )
        );
        
        $properties = array(
            'knownMaps' => $maps,
            'resolvedMaps' => $resolvedMaps,
            'schedule' => $schedule,
            'scheduleColumnMap' => $scheduleColumnMap,
            'scheduleConditionMap' => $scheduleConditionMap,
            'unresolvedScheduleColumn' => $unresolvedScheduleColumn,
            'unresolvedScheduleCondition' => $unresolvedScheduleCondition,
            'unresolvedSchedulePrimaryKey' => $unresolvedSchedulePrimaryKey
        );
        
        return array(
            'resolved_some_maps' => array(
                $properties,
                array(
                    array(
                        '0/update/table1',
                        0
                    ),
                    array(
                        '0/delete/table1',
                        0
                    ),
                    array(
                        '0/delete/table1',
                        0
                    ),
                    array(
                        '0/update/table1',
                        0
                    ),
                    array(
                        '0/insert/table1',
                        2
                    ),
                    array(
                        '0/update/table1',
                        0
                    ),
                    array(
                        '0/delete/table1',
                        0
                    ),
                    array(
                        '0/update/table1',
                        0
                    ),
                    array(
                        '0/insert/table1',
                        3
                    )
                ),
                $this->replaceArrayValues($schedule, array(
                    '0/insert/table1' => array(
                        0 => array(
                            'column1' => 1
                        ),
                        1 => array(
                            'column1' => 2
                        ),
                        2 => array(
                            'column1' => 3,
                            'column3' => 2 
                        ),
                        3 => array(
                            'column3' => 3
                        )
                    ),
                    '0/update/table1' => array(
                        0 => array(
                            'data' => array(
                                'column2' => 'text_value2',
                                'column3' => 1
                            ),
                            'condition' => array(
                                'column1' => 2,
                                'column2' => array(1, 2)
                            )
                        )
                    ),
                    '0/delete/table1' => array(
                        0 => array(
                            'column1' => array(
                                0 => 1,
                                1 => 2
                            ),
                            'column2' => 1
                        )
                    )
                )),
                array(
                    '0/insert/table1' => array(
                        2 => array(),
                        3 => array(),
                        4 => array(
                            'column3' => 'map4'
                        ),
                        5 => array(
                            'column3' => 'map5'
                        )
                    ),
                    '0/update/table1' => array(
                        0 => array()
                    )
                ),
                array(
                    '0/update/table1' => array(
                        0 => array()
                    ),
                    '0/delete/table1' => array(
                        0 => array()
                    )
                ),
                array(
                    '0/insert/table1' => array(
                        'map4' => 3,
                        'map5' => 4,
                        'map6' => 5
                    )
                )
            ),
            'no_maps_found' => array(
                array('resolvedMaps' => array()) + $properties,
                array(),
                $schedule,
                $unresolvedScheduleColumn,
                $unresolvedScheduleCondition,
                $unresolvedSchedulePrimaryKey
            )
        );
    }
    
    protected function replaceArrayValues($original, $keys)
    {
        foreach ($keys as $key => $rows) {
            foreach ($rows as $rowId => $columns) {
                foreach ($columns as $column => $value) {
                    $original[$key][$rowId][$column] = $value;
                }
            }
        }
        
        return $original;
    }

    /**
     * @param $unresolvedPrimaryKeys
     * @param $resolvedSchedule
     * @param int $queue
     * @param string $table
     * @param bool $expectedResult
     * @dataProvider dataProviderIsInsertScheduleMultiple
     */
    public function testItChecksCorrectlyIfCurrentScheduleMultiple(
        $resolvedSchedule,
        $unresolvedPrimaryKeys,
        $queue, $table,
        $expectedResult)
    {
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container, 
            array(
                'resolvedSchedule' => $resolvedSchedule,
                'unresolvedSchedulePrimaryKey' => $unresolvedPrimaryKeys
            )
        );
        
        $this->assertSame(
            $expectedResult,
            $this->container->isInsertScheduleMultiple($queue, $table)
        );
    }
    
    public function dataProviderIsInsertScheduleMultiple()
    {
        $resolvedSchedule = array(
            '0/insert/table1' => array_combine(range(0, 4, 1), range(0, 4, 1)),
            '0/insert/table2' => array_combine(range(0, 4, 1), range(0, 4, 1)),
            '0/insert/table3' => array_combine(range(0, 4, 1), range(0, 4, 1)),
            '0/insert/table4' => array_combine(range(0, 4, 1), range(0, 4, 1)),
            '0/insert/table5' => array(),            
        );
        
        $unresolvedPrimaryKeys = array(
            '0/insert/table1' => array(),
            '0/insert/table2' => array(
                'map2' => 1,
                'map1' => 0,
                'map4' => 3
            ),
            '0/insert/table3' => array(
                'map3' => 5
            ),
            '0/insert/table4' => array(
                'map4' => 0
            ),
            '0/insert/table5' => array(
                'map4' => 0
            )
        );
        
        return array(
            'all_items_resolved' => array(
                $resolvedSchedule,
                $unresolvedPrimaryKeys,
                0,
                'table1',
                true
            ),
            'multiple_items_unresolved' => array(
                $resolvedSchedule,
                $unresolvedPrimaryKeys,
                0,
                'table2',
                false
            ),
            'unrelated_item_unresolved' => array(
                $resolvedSchedule,
                $unresolvedPrimaryKeys,
                0,
                'table3',
                true
            ),
            'one_item_unresolved' => array(
                $resolvedSchedule,
                $unresolvedPrimaryKeys,
                0,
                'table4',
                false
            ),
            'no_items_resolved' => array(
                $resolvedSchedule,
                $unresolvedPrimaryKeys,
                0,
                'table5',
                false
            )
        );
    }

    /**
     * 
     * @dataProvider dataProviderUnresolvedPrimaryKey
     */
    public function testItReturnsUnresolvedPrimaryKeyIfItExists(
        $properties, $queue, $table, $rowIndex, $expectedPrimaryKey
    )
    {
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container, 
            $properties
        );
        
        $this->assertSame(
            $expectedPrimaryKey, 
            $this->container->getInsertSchedulePrimaryKeyMap($queue, $table, $rowIndex)
        );
    }

    public function dataProviderUnresolvedPrimaryKey()
    {
        $properties = array(
            'unresolvedSchedulePrimaryKey' => array(
                '0/insert/table2' => array(
                    'map1' => 0,
                    'map3' => 2
                ),
                '0/insert/table3' => array(
                    'map2' => 0
                ),
                '0/insert/table4' => array(
                    'map4' => 3
                )
            ),
            'knownMaps' => array(
                'map1' => new EcomDev_Fixture_Db_Map_Static('table1'),
                'map2' => new EcomDev_Fixture_Db_Map_Static('table2'),
                'map3' => new EcomDev_Fixture_Db_Map_Static('table2'),
                'map4' => new EcomDev_Fixture_Db_Map_Static('table3')
            )
        );

        return array(
            'table1_row_1' => array($properties, 0, 'table1', 0, false),
            'table1_row_2' => array($properties, 0, 'table1', 1, false),
            'table1_row_3' => array($properties, 0, 'table1', 2, false),
            'table2_row_1' => array($properties, 0, 'table2', 0, $properties['knownMaps']['map1']),
            'table2_row_2' => array($properties, 0, 'table2', 1, false),
            'table2_row_3' => array($properties, 0, 'table2', 2, $properties['knownMaps']['map3']),
            'table3_row_1' => array($properties, 0, 'table3', 0, $properties['knownMaps']['map2']),
            'table3_row_2' => array($properties, 0, 'table3', 1, false),
            'table3_row_3' => array($properties, 0, 'table3', 2, false),
            'table4_row_1' => array($properties, 0, 'table4', 0, false),
            'table4_row_2' => array($properties, 0, 'table4', 1, false),
            'table4_row_3' => array($properties, 0, 'table4', 2, false),
            'table4_row_4' => array($properties, 0, 'table4', 3, $properties['knownMaps']['map4'])
        );
    }
    
    public function testItClearsPropertiesOfItems()
    {
        $dummyData = array('item1' => 'item1');
        $mapOne = $this->getMock('EcomDev_Fixture_Db_Map_Static', array('reset'), array('table1'));
        $mapTwo = $this->getMock('EcomDev_Fixture_Db_Map_Static', array('reset'), array('table1'));
        $mapOne->expects($this->once())
            ->method('reset')
            ->willReturnSelf();
        $mapTwo->expects($this->once())
            ->method('reset')
            ->willReturnSelf();
        
        $properties = array(
            'knownMaps' => array(
                'map1' => $mapOne, 
                'map2' => $mapTwo
            ),
            'schedule' => $dummyData,
            'scheduleColumnMap' => $dummyData,
            'scheduleConditionMap' => $dummyData,
            'resolvedSchedule' => $dummyData,
            'resolvedMaps' => $dummyData,
            'unresolvedSchedulePrimaryKey' => $dummyData,
            'unresolvedScheduleColumn' => $dummyData,
            'unresolvedScheduleCondition' => $dummyData
        );
        
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container,
            $properties
        );
        
        $this->assertSame($this->container, $this->container->reset());
        
        foreach (array_keys($properties) as $property) {
            $this->assertAttributeEmpty(
                $property, 
                $this->container, 
                'Property ' . $property . ' is not empty'
            );
        }
    }

    /**
     * @param array $schedule
     * @param string[] $tableNamesOrdered
     * @param $queue
     * @param $type
     * @param $expectedTables
     * @dataProvider dataProviderScheduledTablesOrder
     */
    public function testScheduleTablesOrder(
        $schedule, $tableNamesOrdered, $queue, $type, $expectedTables
    )
    {
        ReflectionUtil::setRestrictedPropertyValue(
            $this->container,
            'schedule',
            $schedule
        );
        
        $this->schemaMock->expects($this->once())
            ->method('getTableNamesSortedByRelation')
            ->willReturn($tableNamesOrdered);
        
        $this->assertSame(
            $expectedTables, 
            $this->container->getScheduleTables($queue, $type)
        );
    }
    
    public function dataProviderScheduledTablesOrder()
    {
        $schedule = array(
            '0/insert' => array(
                'table1' => array(),
                'table4' => array(),
                'table2' => array(),
                'table6' => array(),
                'table3' => array()
            ),
            '0/update' => array(
                'table1' => array(),
                'table4' => array(),
                'table3' => array()
            ),
            '0/delete' => array(
                'table4' => array(),
                'table3' => array(),
                'table1' => array()
            )
        );
        
        $orderOfTables = array();
        
        for ($index = 1, $total = 100; $index <= $total; $index ++) {
            $orderOfTables[] = 'table' . $index;
        } 
        
        return array(
            array(
                $schedule,
                $orderOfTables,
                0,
                'insert',
                array(
                    0 => 'table1', 
                    1 => 'table2', 
                    2 => 'table3', 
                    3 => 'table4', 
                    5 => 'table6'
                )
            ),
            array(
                $schedule,
                $orderOfTables,
                0,
                'update',
                array(
                    0 => 'table1', 
                    2 => 'table3', 
                    3 => 'table4'
                )
            ),
            array(
                $schedule,
                $orderOfTables,
                0,
                'delete',
                array(
                    0 => 'table1', 
                    2 => 'table3', 
                    3 => 'table4'
                )
            )
        );
    }

    /**
     * @param $schedule
     * @param $queue
     * @param $type
     * @param $table
     * @param $expectedResult
     * @dataProvider dataProviderHasSchedule
     */
    public function testItProperlyChecksScheduleAvailability($schedule, $queue, $type, $table, $expectedResult)
    {
        ReflectionUtil::setRestrictedPropertyValue(
            $this->container,
            'schedule',
            $schedule
        );

        $this->assertSame($expectedResult, $this->container->hasSchedule($queue, $table, $type));
    }
    
    public function dataProviderHasSchedule()
    {
        $schedule = array(
            '0/insert' => array(
                'table1' => array(
                    array(
                        'column1' => 'value'
                    ),
                ),
                'table2' => array(
                    array(
                        'column1' => 'value'
                    )
                ),
                'table3' => array(),
                'table5' => array(),
            ),
            '0/update' => array(
                'table1' => array(
                    array(
                        'data' => array('column1' => 'value'),
                        'condition' => array()
                    )
                ),
                'table4' => array(),
                'table3' => array()
            ),
            '0/delete' => array(
                'table3' => array(
                    array('column1' => 'value')
                )
            )
        );

        return array(
            array($schedule, 0, 'insert', 'table1', true),
            array($schedule, 0, 'insert', 'table2', true),
            array($schedule, 0, 'insert', 'table3', false),
            array($schedule, 0, 'insert', 'table4', false),
            array($schedule, 0, 'insert', 'table5', false),
            array($schedule, 0, 'update', 'table1', true),
            array($schedule, 0, 'update', 'table2', false),
            array($schedule, 0, 'update', 'table3', false),
            array($schedule, 0, 'delete', 'table1', false),
            array($schedule, 0, 'delete', 'table2', false),
            array($schedule, 0, 'delete', 'table3', true),
        );
    }

    /**
     * @param array $properties
     * @param int $queue
     * @param string $type
     * @param string $table
     * @param array $expectedResult
     * @param array $expectedResolvedSchedule
     * @param array $expectedSchedule
     * @dataProvider dataProviderGetSchedule
     */
    public function testItRetrievesCorrectlySchedule(
        $properties, $queue, $type, $table, $expectedResult, 
        $expectedResolvedSchedule, $expectedSchedule) 
    {
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container,
            $properties
        );
        
        $rows = $this->container->getSchedule($queue, $table, $type);
        $this->assertSame($expectedResult, $rows);
        $this->assertAttributeEquals(
            $expectedResolvedSchedule,
            'resolvedSchedule',
            $this->container
        );
        $this->assertAttributeEquals(
            $expectedSchedule,
            'schedule',
            $this->container
        );
    }

    public function dataProviderGetSchedule()
    {
        $schedule = array(
            '0/insert' => array(
                'table1' => array(
                    array('column1' => 'value1'),
                    array('column1' => 'value2'),
                    array('column1' => 'value3'),
                    array('column1' => 'value4'),
                    array('column1' => 'value5'),
                    array('column1' => 'value5')
                ),
                'table2' => array(
                    array('column1' => 'value1'),
                    array('column1' => 'value2'),
                    array('column1' => 'value3')
                ),
                'table3' => array()
            )
        );
        
        $resolvedSchedule = array(
            '0/insert/table1' => array(
                0 => 0,
                1 => 1,
                2 => 2
            ),
            '0/insert/table2' => array(
                2 => 2
            )
        );
        
        $properties = array(
            'schedule' => $schedule,
            'resolvedSchedule' => $resolvedSchedule
        );

        return array(
            'table1' => array(
                $properties,
                0, 'insert', 'table1',
                array(
                    array('column1' => 'value1'),
                    array('column1' => 'value2'),
                    array('column1' => 'value3')
                ),
                array('0/insert/table1' => array()) + $resolvedSchedule,
                array(
                    '0/insert' => array(
                        'table1' => array(
                            3 => $schedule['0/insert']['table1'][3],
                            4 => $schedule['0/insert']['table1'][4],
                            5 => $schedule['0/insert']['table1'][5]
                        ),
                        'table2' => $schedule['0/insert']['table2'],
                        'table3' => $schedule['0/insert']['table3'],
                    )
                )
            ),
            'table2' => array(
                $properties,
                0, 'insert', 'table2',
                array(
                    array('column1' => 'value3')
                ),
                array('0/insert/table2' => array()) + $resolvedSchedule,
                array(
                    '0/insert' => array(
                        'table1' => $schedule['0/insert']['table1'],
                        'table2' => array(
                            0 => $schedule['0/insert']['table2'][0],
                            1 => $schedule['0/insert']['table2'][1]
                        ),
                        'table3' => $schedule['0/insert']['table3']
                    )
                )
            ),
            'table3' => array(
                $properties,
                0, 'insert', 'table3',
                array(),
                $resolvedSchedule,
                $schedule
            ),
            'table4' => array(
                $properties,
                0, 'insert', 'table4',
                array(),
                $resolvedSchedule,
                $schedule
            ),
        );
    }

    /**
     * @param $properties
     * @param $queue
     * @param $type
     * @param $table
     * @param $expectedErrors
     * @dataProvider dataProviderResolveErrors
     */
    public function testItCorrectlyReturnsResolveErrors($properties, $queue, $type, $table, $expectedErrors)
    {
        ReflectionUtil::setRestrictedPropertyValues(
            $this->container,
            $properties
        );
        
        $this->assertEquals(
            $expectedErrors, 
            $this->container->getScheduleResolveErrors($queue, $table, $type)
        );
    }
    
    public function dataProviderResolveErrors()
    {
        $maps = array(
            'map1' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map2' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map3' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map4' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map5' => new EcomDev_Fixture_Db_Map_Static('table1'),
            'map6' => new EcomDev_Fixture_Db_Map_Static('table1'),
        );

        $schedule = array(
            '0/insert' => array( 
                'table1' => array(
                    3 => array(
                        'column1' => $maps['map4'],
                        'column2' => 'text_value',
                        'column3' => $maps['map3']
                    ),
                    4 => array(
                        'column1' => $maps['map5'],
                        'column2' => 'text_value',
                        'column3' => $maps['map4']
                    ),
                    5 => array(
                        'column1' => $maps['map6'],
                        'column2' => 'text_value',
                        'column3' => $maps['map5']
                    )
                )
            ),
            '0/update' => array(
                'table1' => array(
                    0 => array(
                        'data' => array(
                            'column2' => 'text_value2',
                            'column3' => $maps['map1']
                        ),
                        'condition' => array(
                            'column1' => $maps['map2']
                        )
                    )
                )
            ),
            '0/delete' => array(
                'table1' => array(
                    0 => array(
                        'column1' => array(
                            0 => $maps['map1'],
                            1 => $maps['map2']
                        )
                    )
                )
            )
        );

        $unresolvedScheduleColumn = array(
            '0/insert/table1' => array(
                4 => array(
                    'column3' => 'map4'
                ),
                5 => array(
                    'column3' => 'map5'
                )
            ),
            '0/update/table1' => array()
        );

        $unresolvedScheduleCondition = array(
            '0/update/table1' => array(
                0 => array(
                    'column1' => array(
                        'map2' => true
                    )
                )
            ),
            '0/delete/table1' => array(
                0 => array(
                    'column1' => array(
                        'map1' => 0,
                        'map2' => 1
                    )
                )
            )
        );

        $properties = array(
            'knownMaps' => $maps,
            'schedule' => $schedule,
            'unresolvedScheduleColumn' => $unresolvedScheduleColumn,
            'unresolvedScheduleCondition' => $unresolvedScheduleCondition
        );
        
        return array(
            array(
                $properties,
                0,
                'insert',
                'table1',
                array(
                    new EcomDev_Fixture_Db_Writer_Error(
                        'Column "column3" has unresolved map to external entity',
                        'insert',
                        'table1',
                        0,
                        4,
                        array('map' => $maps['map4'])
                    ),
                    new EcomDev_Fixture_Db_Writer_Error(
                        'Column "column3" has unresolved map to external entity',
                        'insert',
                        'table1',
                        0,
                        5,
                        array('map' => $maps['map5'])
                    )
                )
            ),
            array(
                $properties,
                0,
                'insert',
                'table2',
                array()
            ),
            array(
                $properties,
                0,
                'update',
                'table1',
                array(
                    new EcomDev_Fixture_Db_Writer_Error(
                        'Condition "column1" has unresolved map to external entity',
                        'update',
                        'table1',
                        0,
                        0,
                        array('map' => $maps['map2'])
                    )
                )
            ),
            array(
                $properties,
                0,
                'delete',
                'table1',
                array(
                    new EcomDev_Fixture_Db_Writer_Error(
                        'Condition "column1" has unresolved map to external entity at "0" index',
                        'delete',
                        'table1',
                        0,
                        0,
                        array('map' => $maps['map1'])
                    ),
                    new EcomDev_Fixture_Db_Writer_Error(
                        'Condition "column1" has unresolved map to external entity at "1" index',
                        'delete',
                        'table1',
                        0,
                        0,
                        array('map' => $maps['map2'])
                    )
                )
            )
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     */
    public function testItSchedulesInsertForPrimaryKeyColumnWithNoMap()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER,
            Column::TYPE_TEXT
        ));
        
        $columns = $table->getColumns();
            
        $this->mockTable($table);
        
        $this->container->expects($this->exactly(3))
            ->method('prepareColumnValue')
            ->withConsecutive(
                array($table, $columns['column2'], 'value2'),
                array($table, $columns['column3'], null),
                array($table, $columns['column4'], 'value4')
            )
            ->willReturnOnConsecutiveCalls(
                'value2', 1, 'value4'
            );
        
        $this->container->expects($this->never())
            ->method('registerColumnMap');

       $this->checkInsertSchedule(
           $table->getName(),
           array(
               'column2' => 'value2',
               'column4' => 'value4'
           )
           , 
           array(
               0 => array(
                   'column1' => null,
                   'column2' => 'value2',
                   'column3' => 1,
                   'column4' => 'value4'
               )
           )
       );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     */
    public function testItSchedulesInsertForPrimaryKeyColumnWithMapThatIsSpecifiedAsArgument()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER
        ));

        $columns = $table->getColumns();

        $mapOne = new EcomDev_Fixture_Db_Map_Static('table1');
        $mapTwo = new EcomDev_Fixture_Db_Map_Static('table1');
        
        $this->mockTable($table);

        $this->container->expects($this->exactly(2))
            ->method('prepareColumnValue')
            ->withConsecutive(
                array($table, $columns['column2'], 'value2'),
                array($table, $columns['column3'], $mapTwo)
            )
            ->willReturnOnConsecutiveCalls(
                'value2', $mapTwo
            );
        
        $this->container->expects($this->exactly(2))
            ->method('registerColumnMap')
            ->withConsecutive(
                array($mapOne, $columns['column1'], '0/insert/table1', 0),
                array($mapTwo, $columns['column3'], '0/insert/table1', 0)
            )
            ->willReturnSelf()
        ;

        $this->checkInsertSchedule(
            $table->getName(),
            array(
                'column1' => $mapOne,
                'column2' => 'value2',
                'column3' => $mapTwo,
                'some_redundant_data' => 'data1'
            ),
            array(
                0 => array(
                    'column1' => $mapOne,
                    'column2' => 'value2',
                    'column3' => $mapTwo
                )
            ),
            null
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     */
    public function testItSchedulesInsertForPrimaryKeyColumnWithMap()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER
        ));

        $columns = $table->getColumns();

        $mapOne = new EcomDev_Fixture_Db_Map_Static('table1');
        $mapTwo = new EcomDev_Fixture_Db_Map_Static('table1');

        $this->mockTable($table);

        $this->container->expects($this->exactly(2))
            ->method('prepareColumnValue')
            ->withConsecutive(
                array($table, $columns['column2'], 'value2'),
                array($table, $columns['column3'], $mapTwo)
            )
            ->willReturnOnConsecutiveCalls(
                'value2', $mapTwo
            );

        $this->container->expects($this->exactly(2))
            ->method('registerColumnMap')
            ->withConsecutive(
                array($mapOne, $columns['column1'], '0/insert/table1', 0),
                array($mapTwo, $columns['column3'], '0/insert/table1', 0)
            )
            ->willReturnSelf()
        ;

        $this->checkInsertSchedule(
            $table->getName(),
            array(
                'column2' => 'value2',
                'column3' => $mapTwo,
                'some_redundant_data' => 'data1'
            ),
            array(
                0 => array(
                    'column1' => $mapOne,
                    'column2' => 'value2',
                    'column3' => $mapTwo
                )
            ),
            $mapOne
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The primary key "column1" for "table1" is required, since it is not autoincrement based.
     */
    public function testItDoesNotScheduleInsertForPrimaryKeyColumnNoValue()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER
        ));

        $this->mockTable($table);

        $this->container->expects($this->never())
            ->method('prepareColumnValue');

        $this->container->expects($this->never())
            ->method('registerColumnMap');

        $this->checkInsertSchedule(
            $table->getName(),
            array(
                'column2' => 'value'
            ),
            array(),
            false
        );
    }
    
    
    
    protected function checkInsertSchedule(
        $tableName, $data, $expectedSchedule, $idMap = false,
        $queue = EcomDev_Fixture_Db_Writer_Container::QUEUE_PRIMARY)
    {
        if ($idMap === null) {
            $this->container->expects($this->never())
                ->method('registerRowMap');
        } else {
            $this->container->expects($this->once())
                ->method('registerRowMap')
                ->with($tableName, $data)
                ->willReturn($idMap);
        }

        $this->assertSame(
            $this->container,
            $this->container->scheduleInsert('table1', $data, $queue)
        );

        $this->assertAttributeSame(
            array(
                '0/insert' => array(
                    $tableName => $expectedSchedule
                )
            ),
            'schedule',
            $this->container
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     * @mockMethod registerConditionMap
     */
    public function testItSchedulesUpdate()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER,
            Column::TYPE_TEXT
        ));

        $columns = $table->getColumns();

        $mapOne = new EcomDev_Fixture_Db_Map_Static('table1');
        $mapTwo = new EcomDev_Fixture_Db_Map_Static('table1');

        $this->mockTable($table);

        $this->container->expects($this->exactly(2))
            ->method('prepareColumnValue')
            ->withConsecutive(
                array($table, $columns['column2'], 'value2'),
                array($table, $columns['column3'], $mapTwo)
            )
            ->willReturnOnConsecutiveCalls(
                'value2', $mapTwo
            );

        $this->container->expects($this->once())
            ->method('registerColumnMap')
            ->with($mapTwo, $columns['column3'], '0/update/table1', 0)
            ->willReturnSelf()
        ;

        $this->container->expects($this->exactly(3))
            ->method('registerConditionMap')
            ->withConsecutive(
                array($mapOne, 'column1 = ?', '0/update/table1', 0),
                array($mapOne, 'column3 IN(?)', '0/update/table1', 0, 0),
                array($mapOne, 'column3 IN(?)', '0/update/table1', 0, 1)
            )
            ->willReturnSelf()
        ;

        $this->assertSame(
            $this->container,
            $this->container->scheduleUpdate(
                'table1',
                array(
                    'column2' => 'value2',
                    'column3' => $mapTwo
                ),
                array(
                    'column1' => $mapOne,
                    'column3' => array($mapOne, $mapTwo),
                    'column2' => null,
                    'column4 IS NOT NULL' => null
                )
            )
        );

        $this->assertAttributeSame(
            array(
                '0/update' => array(
                    'table1' => array(
                        0 => array(
                            'data' => array(
                                'column2' => 'value2',
                                'column3' => $mapTwo
                            ),
                            'condition' => array(
                                'column4 IS NOT NULL' => null,
                                'column1 = ?' => $mapOne,
                                'column2 IS NULL' => null,
                                'column3 IN(?)' => array($mapOne, $mapTwo)
                            )
                        )
                    )
                )
            ),
            'schedule',
            $this->container
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     * @mockMethod registerConditionMap
     */
    public function testItSchedulesEmptyConditionUpdate()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER,
            Column::TYPE_TEXT
        ));

        $columns = $table->getColumns();
        $this->mockTable($table);

        $this->container->expects($this->once())
            ->method('prepareColumnValue')
            ->with(
                $table, $columns['column2'], 'value2'
            )
            ->willReturn('value2');

        $this->container->expects($this->never())
            ->method('registerColumnMap')
        ;

        $this->container->expects($this->never())
            ->method('registerConditionMap')
        ;

        $this->assertSame(
            $this->container,
            $this->container->scheduleUpdate(
                'table1',
                array(
                    'column2' => 'value2'
                )
            )
        );

        $this->assertAttributeSame(
            array(
                '0/update' => array(
                    'table1' => array(
                        0 => array(
                            'data' => array(
                                'column2' => 'value2'
                            ),
                            'condition' => array()
                        )
                    )
                )
            ),
            'schedule',
            $this->container
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     * @mockMethod registerConditionMap
     */
    public function testItSchedulesDelete()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER,
            Column::TYPE_TEXT
        ));

        $this->mockTable($table);
        
        $mapOne = new EcomDev_Fixture_Db_Map_Static('table1');
        $mapTwo = new EcomDev_Fixture_Db_Map_Static('table1');


        $this->container->expects($this->never())
            ->method('prepareColumnValue');

        $this->container->expects($this->never())
            ->method('registerColumnMap');

        $this->container->expects($this->exactly(3))
            ->method('registerConditionMap')
            ->withConsecutive(
                array($mapOne, 'column1 = ?', '0/delete/table1', 0),
                array($mapOne, 'column3 IN(?)', '0/delete/table1', 0, 0),
                array($mapOne, 'column3 IN(?)', '0/delete/table1', 0, 1)
            )
            ->willReturnSelf()
        ;

        $this->assertSame(
            $this->container,
            $this->container->scheduleDelete(
                'table1',
                array(
                    'column1' => $mapOne,
                    'column3' => array($mapOne, $mapTwo),
                    'column2' => null,
                    'column4 IS NOT NULL' => null
                )
            )
        );

        $this->assertAttributeSame(
            array(
                '0/delete' => array(
                    'table1' => array(
                        0 => array(
                            'column4 IS NOT NULL' => null,
                            'column1 = ?' => $mapOne,
                            'column2 IS NULL' => null,
                            'column3 IN(?)' => array($mapOne, $mapTwo)
                        )
                    )
                )
            ),
            'schedule',
            $this->container
        );
    }

    /**
     * @mockMethod prepareColumnValue
     * @mockMethod registerRowMap
     * @mockMethod registerColumnMap
     * @mockMethod registerConditionMap
     */
    public function testItSchedulesDeleteWithEmptyCondition()
    {
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_TEXT,
            Column::TYPE_INTEGER,
            Column::TYPE_TEXT
        ));

        $this->mockTable($table);

        $this->container->expects($this->never())
            ->method('prepareColumnValue');

        $this->container->expects($this->never())
            ->method('registerColumnMap');

        $this->container->expects($this->never())
            ->method('registerConditionMap');

        $this->assertSame(
            $this->container,
            $this->container->scheduleDelete(
                'table1',
                array()
            )
        );

        $this->assertAttributeSame(
            array(
                '0/delete' => array(
                    'table1' => array(
                        0 => array()
                    )
                )
            ),
            'schedule',
            $this->container
        );
    }
    
    protected function createColumn($columnName, $type, $options = 0)
    {
        return new Column($columnName, $type, null, null, null, $options);
    }
    
    protected function createTable($tableName, $columns = array())
    {
        foreach ($columns as $index => $columnData) {
            if (!is_array($columnData)) {
                $type = $columnData;
                $options = 0;
            } else {
                $type = $columnData[0];
                $options = $columnData[1];
            }
            
            $columns[$index] = $this->createColumn('column' .  ($index + 1), $type, $options);
        }
        
        return new Table($tableName, $columns);
    }

    /**
     * @param Table $table
     * @param int $times
     * @return $this
     */
    protected function mockTable($table, $times = 1)
    {
        if ($times === 1) {
            $times = $this->once();
        } elseif ($times === 0) {
            $times = $this->never();
        } else {
            $times = $this->exactly($times);
        }
        
        $this->schemaMock->expects($times)
            ->method('getTableInfo')
            ->with($table->getName())
            ->willReturn($table);
        return $this;
    }
}
