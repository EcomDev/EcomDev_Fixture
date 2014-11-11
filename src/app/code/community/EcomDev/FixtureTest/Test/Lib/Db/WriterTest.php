<?php

use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_Writer_ContainerInterface as ContainerInterface;
use EcomDev_Fixture_Db_Writer as Writer;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Fixture_Db_Schema_Table as Table;
use EcomDev_PHPUnit_Test_Case_Util as TestUtil;


class EcomDev_FixtureTest_Test_Lib_Db_WriterTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * Adapter mock object
     * 
     * @var Varien_Db_Adapter_Pdo_Mysql|PHPUnit_Framework_MockObject_MockObject
     */
    protected $adapterMock;

    /**
     * @var SchemaInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $schemaMock;

    /**
     * @var ResolverInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolverMock;

    /**
     * @var ContainerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerMock;

    /**
     * @var Writer|PHPUnit_Framework_MockObject_MockObject
     */
    protected $writer;

    /**
     * Prepares mock objects
     * 
     * @return void
     */
    protected function setUp()
    {
        $this->adapterMock = $this->getMockBuilder('Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        
        $this->schemaMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_SchemaInterface');

        $mockResolver = TestUtil::getAnnotationByNameFromClass(__CLASS__, 'mockResolver', 'method', $this->getName(false));
        $mockContainer = TestUtil::getAnnotationByNameFromClass(__CLASS__, 'mockContainer', 'method', $this->getName(false));
        $mockMethods = TestUtil::getAnnotationByNameFromClass(__CLASS__, 'mockMethod', 'method', $this->getName(false));
        
        if ($mockResolver) {
            $this->resolverMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_ResolverInterface');
        }
        
        if ($mockContainer) {
            $this->containerMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Writer_ContainerInterface');
        }
        
        if ($mockMethods) {
            $this->writer = $this->getMock(
                'EcomDev_Fixture_Db_Writer', $mockMethods, 
                array($this->adapterMock, $this->schemaMock, $this->resolverMock, $this->containerMock)
            );
        } else {
            $this->writer = new Writer($this->adapterMock, $this->schemaMock, $this->resolverMock, $this->containerMock);
        }
    }

    /**
     * @mockResolver
     */
    public function testItHasRequiredAttributes()
    {   
        $this->assertObjectHasAttribute('adapter', $this->writer);
        $this->assertObjectHasAttribute('schema', $this->writer);
        $this->assertObjectHasAttribute('resolver', $this->writer);
        $this->assertObjectHasAttribute('container', $this->writer);
        $this->assertObjectHasAttribute('batchSize', $this->writer);
        $this->assertObjectHasAttribute('errors', $this->writer);
    }

    /**
     * @mockResolver
     * @mockContainer
     */
    public function testItStoresPassedDependenciesProperty()
    {
        $this->assertAttributeSame($this->adapterMock, 'adapter', $this->writer);
        $this->assertAttributeSame($this->schemaMock, 'schema', $this->writer);
        $this->assertAttributeSame($this->resolverMock, 'resolver', $this->writer);
        $this->assertAttributeSame($this->containerMock, 'container', $this->writer);
        
        $this->assertEquals($this->adapterMock, $this->writer->getAdapter());
        $this->assertEquals($this->schemaMock, $this->writer->getSchema());
        $this->assertEquals($this->resolverMock, $this->writer->getResolver());
        $this->assertEquals($this->containerMock, $this->writer->getContainer());
    }

    public function testItSetsResolverToDefaultOneIfOtherIsNotSpecified()
    {
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Db_Resolver', 'resolver', $this->writer);
    }

    public function testItSetsContainerToDefaultOneIfOtherIsNotSpecified()
    {
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Db_Writer_Container', 'container', $this->writer);
    }

    public function testItHasDefaultBatchSizeEqualToRecommendedValue()
    {
        $this->assertAttributeSame(EcomDev_Fixture_Contract_Db_WriterInterface::DEFAULT_BATCH_SIZE, 'batchSize', $this->writer);
        $this->assertEquals(EcomDev_Fixture_Contract_Db_WriterInterface::DEFAULT_BATCH_SIZE, $this->writer->getBatchSize());
    }
    
    public function testItIsPossibleToSetBatchSize()
    {
        $this->assertSame($this->writer, $this->writer->setBatchSize(100));
        $this->assertEquals(100, $this->writer->getBatchSize());
    }

    public function testItIsPossibleToRetrieveInitialStats()
    {
        $this->assertEquals(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }
    
    /**
     * @mockContainer
     */
    public function testItForwardsScheduleInsertToContainerWithDefaultQueue()
    {
        $this->containerMock->expects($this->once())
            ->method('scheduleInsert')
            ->with('table1', array('data' => 'dummy'), ContainerInterface::QUEUE_PRIMARY)
            ->willReturnSelf();
        
        $this->assertSame($this->writer, $this->writer->scheduleInsert('table1', array('data' => 'dummy')));
    }

    /**
     * @mockContainer
     */
    public function testItForwardsScheduleInsertToContainerWithSecondaryQueue()
    {
        $this->containerMock->expects($this->once())
            ->method('scheduleInsert')
            ->with('table1', array('data' => 'dummy'), ContainerInterface::QUEUE_SECONDARY)
            ->willReturnSelf();

        $this->assertSame($this->writer, $this->writer->scheduleInsert('table1', array('data' => 'dummy'), ContainerInterface::QUEUE_SECONDARY));
    }

    /**
     * @mockContainer
     */
    public function testItForwardsScheduleUpdateToContainerWithDefaultQueueAndCondition()
    {
        $this->containerMock->expects($this->once())
            ->method('scheduleUpdate')
            ->with('table1', array('data' => 'dummy'), array(), ContainerInterface::QUEUE_PRIMARY)
            ->willReturnSelf();

        $this->assertSame($this->writer, $this->writer->scheduleUpdate('table1', array('data' => 'dummy')));
    }

    /**
     * @mockContainer
     */
    public function testItForwardsScheduleUpdateToContainerWithSecondaryQueueAndCustomCondition()
    {
        $this->containerMock->expects($this->once())
            ->method('scheduleUpdate')
            ->with(
                'table1', 
                array('data' => 'dummy'), 
                array('condition' => 'dummy'), 
                ContainerInterface::QUEUE_SECONDARY
            )
            ->willReturnSelf();

        $this->assertSame(
            $this->writer, 
            $this->writer->scheduleUpdate(
                'table1', 
                array('data' => 'dummy'), 
                array('condition' => 'dummy'), 
                ContainerInterface::QUEUE_SECONDARY
            )
        );
    }

    /**
     * @mockContainer
     */
    public function testItForwardsScheduleDeleteToContainerWithDefaultQueueAndCondition()
    {
        $this->containerMock->expects($this->once())
            ->method('scheduleDelete')
            ->with('table1', array(), ContainerInterface::QUEUE_PRIMARY)
            ->willReturnSelf();

        $this->assertSame($this->writer, $this->writer->scheduleDelete('table1'));
    }

    /**
     * @mockContainer
     */
    public function testItForwardsScheduleDeleteToContainerWithSecondaryQueueAndCustomCondition()
    {
        $this->containerMock->expects($this->once())
            ->method('scheduleDelete')
            ->with(
                'table1',
                array('condition' => 'dummy'),
                ContainerInterface::QUEUE_SECONDARY
            )
            ->willReturnSelf();

        $this->assertSame(
            $this->writer,
            $this->writer->scheduleDelete(
                'table1',
                array('condition' => 'dummy'),
                ContainerInterface::QUEUE_SECONDARY
            )
        );
    }
    
    public function testItIsPossibleToSetContainerClass()
    {
        $container = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Writer_ContainerInterface');
        $this->writer->setContainer($container);
        $this->assertSame($container, $this->writer->getContainer());
    }

    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItIsPossibleFlushInsertTableSchedule()
    {
        $this->containerMock->expects($this->exactly(3))
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturnOnConsecutiveCalls(true, true, false);
        
        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_INTEGER
        ));
        
        $this->mockTable($table);
        
        $columnMap = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_MapInterface');
        $columnMap->expects($this->once())
            ->method('setValue')
            ->with(2)
            ->willReturnSelf();

        $this->resolverMock->expects($this->exactly(3))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();
        
        $this->containerMock->expects($this->exactly(2))
            ->method('resolve')
            ->willReturnSelf();
        
        $this->containerMock->expects($this->exactly(2))
            ->method('isInsertScheduleMultiple')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->containerMock->expects($this->exactly(3))
            ->method('getInsertSchedulePrimaryKeyMap')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY, 'table1', 0),
                array(ContainerInterface::QUEUE_PRIMARY, 'table1', 1),
                array(ContainerInterface::QUEUE_PRIMARY, 'table1', 2)
            )
            ->willReturnOnConsecutiveCalls(false, $columnMap, false);
        
        $this->containerMock->expects($this->exactly(2))
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturnOnConsecutiveCalls(
                array(
                    0 => array(
                        'column1' => 1,
                        'column2' => 2
                    ),
                    1 => array(
                        'column1' => $columnMap,
                        'column2' => 1
                    ),
                    2 => array(
                        'column1' => 3,
                        'column2' => 4
                    )
                ),
                array(
                    3 => array(
                        'column1' => 4,
                        'column2' => 2
                    ),
                    4 => array(
                        'column1' => 5,
                        'column2' => 1
                    ),
                    5 => array(
                        'column1' => 6,
                        'column2' => 4
                    )
                )
            );
        
        $this->containerMock->expects($this->never())
            ->method('getScheduleResolveErrors');

        $this->adapterMock->expects($this->once())
            ->method('lastInsertId')
            ->with('table1')
            ->willReturn(2);

        $this->adapterMock->expects($this->exactly(4))
            ->method('insertOnDuplicate')
            ->withConsecutive(
                array('table1', array(
                    'column1' => 1,
                    'column2' => 2)),
                array('table1', array(
                    'column1' => null,
                    'column2' => 1)),
                array('table1', array(
                    'column1' => 3,
                    'column2' => 4)),
                array('table1', array(
                    3 => array(
                        'column1' => 4,
                        'column2' => 2
                    ),
                    4 => array(
                        'column1' => 5,
                        'column2' => 1
                    ),
                    5 => array(
                        'column1' => 6,
                        'column2' => 4)))
            )
            ->willReturnSelf();
        
        $this->assertSame(
            $this->writer, 
            $this->writer
                ->flushInsertSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );
        
        $this->assertEquals(
            array(
                ContainerInterface::TYPE_INSERT => 6,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }

    public function testItReturnsFalseIfErrorsArrayIsEmpty()
    {
        $this->assertFalse($this->writer->hasErrors());
    }

    public function testItReturnsTrueIfErrorsArrayIsNotEmpty()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->writer, 'errors', array('some_error'));
        $this->assertTrue($this->writer->hasErrors());
    }

    public function testItReturnsValuesFromErrorsProperty()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue($this->writer, 'errors', array('some_error'));
        $this->assertSame(
            array('some_error'),
            $this->writer->getErrors()
        );
    }
    
    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItAddsScheduleErrorsIfThereIsStillScheduleButNoScheduleItems()
    {
        $this->containerMock->expects($this->once())
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturnOnConsecutiveCalls(true);

        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_INTEGER
        ));

        $this->mockTable($table);

        $this->resolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('resolve')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('isInsertScheduleMultiple')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1')
            ->willReturnOnConsecutiveCalls(false);

        $this->containerMock->expects($this->never())
            ->method('getInsertSchedulePrimaryKeyMap');

        $this->containerMock->expects($this->once())
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturn(array());
        
        $this->containerMock->expects($this->once())
            ->method('getScheduleResolveErrors')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturn(array('test_error', 'test_error2'));

        $this->containerMock->expects($this->never())
            ->method('getInsertSchedulePrimaryKeyMap');

        $this->adapterMock->expects($this->never())
            ->method('insertOnDuplicate');

        $this->assertSame(
            $this->writer,
            $this->writer
                ->flushInsertSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );
        
        $this->assertSame(
            array('test_error', 'test_error2'),
            $this->writer->getErrors()
        );

        $this->assertEquals(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }

    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItIsWillSplitDataBatchesOnFlushingInsertSchedule()
    {
        $this->containerMock->expects($this->exactly(2))
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturnOnConsecutiveCalls(true, false);

        $table = $this->createTable('table1', array(
            array(Column::TYPE_INTEGER, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY),
            Column::TYPE_INTEGER
        ));

        $this->mockTable($table);
        
        $this->resolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('resolve')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('isInsertScheduleMultiple')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1')
            ->willReturnOnConsecutiveCalls(true);

        $this->containerMock->expects($this->never())
            ->method('getInsertSchedulePrimaryKeyMap');

        $this->containerMock->expects($this->once())
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_INSERT)
            ->willReturn(
                array(
                    0 => array('column2' => 1),
                    1 => array('column2' => 2),
                    2 => array('column2' => 3),
                    3 => array('column2' => 4),
                    4 => array('column2' => 5),
                    5 => array('column2' => 6),
                    6 => array('column2' => 7),
                    7 => array('column2' => 8)
                )
            );

        $this->adapterMock->expects($this->never())
            ->method('lastInsertId');
        
        $this->adapterMock->expects($this->exactly(3))
            ->method('insertOnDuplicate')
            ->withConsecutive(
                array(
                    'table1',
                    array(
                        array('column2' => 1),
                        array('column2' => 2),
                        array('column2' => 3)
                    )
                ),
                array(
                    'table1',
                    array(
                        array('column2' => 4),
                        array('column2' => 5),
                        array('column2' => 6)
                    )
                ),
                array(
                    'table1',
                    array(
                        array('column2' => 7),
                        array('column2' => 8)
                    )
                )
            )
            ->willReturnSelf();

        $this->writer->setBatchSize(3);
        $this->assertSame(
            $this->writer,
            $this->writer
                ->flushInsertSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );

        $this->assertEquals(
            array(
                ContainerInterface::TYPE_INSERT => 8,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }

    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItFlushesUpdateSchedule()
    {
        $this->containerMock->expects($this->exactly(3))
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_UPDATE)
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->resolverMock->expects($this->exactly(3))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();

        $this->containerMock->expects($this->exactly(2))
            ->method('resolve')
            ->willReturnSelf();

        $this->containerMock->expects($this->exactly(2))
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_UPDATE)
            ->willReturnOnConsecutiveCalls(
                array(
                    0 => array(
                        'data' => array('column2' => 'value1'),
                        'condition' => array('column1 = ?' => 1)
                    ),
                    1 => array(
                        'data' => array('column2' => 'value2'),
                        'condition' => array('column1 = ?' => 2)
                    )
                ),
                array(
                    2 => array(
                        'data' => array('column2' => 'value3'),
                        'condition' => array()
                    ),
                    3 => array(
                        'data' => array('column2' => 'value4'),
                        'condition' => array('column1 = ?' => 3)
                    )
                )                
            );
        
        $this->adapterMock->expects($this->exactly(4))
            ->method('update')
            ->withConsecutive(
                array(
                    'table1',
                    array('column2' => 'value1'),
                    array('column1 = ?' => 1)
                ),
                array(
                    'table1',
                    array('column2' => 'value2'),
                    array('column1 = ?' => 2)
                ),
                array(
                    'table1',
                    array('column2' => 'value3')
                ),
                array(
                    'table1',
                    array('column2' => 'value4'),
                    array('column1 = ?' => 3)
                )
            )
            ->willReturnOnConsecutiveCalls(
                1, 1, 4, 1
            );

        $this->assertSame(
            $this->writer,
            $this->writer
                ->flushUpdateSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );
        
        $this->assertSame(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 7,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }

    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItAddsScheduleErrorsIfThereIsStillUpdateScheduleButNoScheduleItems()
    {
        $this->containerMock->expects($this->once())
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_UPDATE)
            ->willReturn(true);

        $this->resolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('resolve')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_UPDATE)
            ->willReturn(array());

        $this->adapterMock->expects($this->never())
            ->method('update');

        $this->containerMock->expects($this->once())
            ->method('getScheduleResolveErrors')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_UPDATE)
            ->willReturn(array('test_error', 'test_error2'));
        
        $this->assertSame(
            $this->writer,
            $this->writer
                ->flushUpdateSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );

        $this->assertSame(
            array('test_error', 'test_error2'),
            $this->writer->getErrors()
        );

        $this->assertEquals(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }

    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItFlushesDeleteSchedule()
    {
        $this->containerMock->expects($this->exactly(3))
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_DELETE)
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->resolverMock->expects($this->exactly(3))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();

        $this->containerMock->expects($this->exactly(2))
            ->method('resolve')
            ->willReturnSelf();

        $this->containerMock->expects($this->exactly(2))
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_DELETE)
            ->willReturnOnConsecutiveCalls(
                array(
                    0 => array('column1 = ?' => 1),
                    1 => array('column1 = ?' => 2)
                ),
                array(
                    2 => array(),
                    3 => array('column1 = ?' => 3)
                )
            );

        $this->adapterMock->expects($this->exactly(4))
            ->method('delete')
            ->withConsecutive(
                array(
                    'table1',
                    array('column1 = ?' => 1)
                ),
                array(
                    'table1',
                    array('column1 = ?' => 2)
                ),
                array(
                    'table1'
                ),
                array(
                    'table1',
                    array('column1 = ?' => 3)
                )
            )
            ->willReturnOnConsecutiveCalls(
                1, 1, 4, 1
            );

        $this->assertSame(
            $this->writer,
            $this->writer
                ->flushDeleteSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );

        $this->assertSame(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 7
            ),
            $this->writer->getStats()
        );
    }

    /**
     * @mockContainer
     * @mockResolver
     */
    public function testItAddsScheduleErrorsIfThereIsStillDeleteScheduleButNoScheduleItems()
    {
        $this->containerMock->expects($this->once())
            ->method('hasSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_DELETE)
            ->willReturn(true);

        $this->resolverMock->expects($this->exactly(2))
            ->method('resolve')
            ->with('table1')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('resolve')
            ->willReturnSelf();

        $this->containerMock->expects($this->once())
            ->method('getSchedule')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_DELETE)
            ->willReturn(array());

        $this->adapterMock->expects($this->never())
            ->method('delete');

        $this->containerMock->expects($this->once())
            ->method('getScheduleResolveErrors')
            ->with(ContainerInterface::QUEUE_PRIMARY, 'table1', ContainerInterface::TYPE_DELETE)
            ->willReturn(array('test_error', 'test_error2'));

        $this->assertSame(
            $this->writer,
            $this->writer
                ->flushDeleteSchedule(ContainerInterface::QUEUE_PRIMARY, 'table1')
        );

        $this->assertSame(
            array('test_error', 'test_error2'),
            $this->writer->getErrors()
        );

        $this->assertEquals(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ),
            $this->writer->getStats()
        );
    }

    /**
     * @mockMethod flushDeleteSchedule
     * @mockContainer
     */
    public function testItFlushesDeleteScheduleForMultipleTables()
    {
        $this->containerMock->expects($this->once())
            ->method('getScheduleTables')
            ->with(ContainerInterface::QUEUE_PRIMARY, ContainerInterface::TYPE_DELETE)
            ->willReturn(array('table1', 'table2'));
        
        $this->writer->expects($this->exactly(2))
            ->method('flushDeleteSchedule')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY, 'table1'),
                array(ContainerInterface::QUEUE_PRIMARY, 'table2')
            )
            ->willReturnSelf()
        ;
        
        $this->assertSame($this->writer, $this->writer->flushDelete(ContainerInterface::QUEUE_PRIMARY));
    }

    /**
     * @mockMethod flushUpdateSchedule
     * @mockContainer
     */
    public function testItFlushesUpdateScheduleForMultipleTables()
    {
        $this->containerMock->expects($this->once())
            ->method('getScheduleTables')
            ->with(ContainerInterface::QUEUE_PRIMARY, ContainerInterface::TYPE_UPDATE)
            ->willReturn(array('table1', 'table2'));

        $this->writer->expects($this->exactly(2))
            ->method('flushUpdateSchedule')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY, 'table1'),
                array(ContainerInterface::QUEUE_PRIMARY, 'table2')
            )
            ->willReturnSelf()
        ;

        $this->assertSame($this->writer, $this->writer->flushUpdate(ContainerInterface::QUEUE_PRIMARY));
    }

    /**
     * @mockMethod flushInsertSchedule
     * @mockContainer
     */
    public function testItFlushesInsertScheduleForMultipleTables()
    {
        $this->containerMock->expects($this->once())
            ->method('getScheduleTables')
            ->with(ContainerInterface::QUEUE_PRIMARY, ContainerInterface::TYPE_INSERT)
            ->willReturn(array('table1', 'table2'));

        $this->writer->expects($this->exactly(2))
            ->method('flushInsertSchedule')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY, 'table1'),
                array(ContainerInterface::QUEUE_PRIMARY, 'table2')
            )
            ->willReturnSelf()
        ;

        $this->assertSame($this->writer, $this->writer->flushInsert(ContainerInterface::QUEUE_PRIMARY));
    }
    
    public function testItClearsStatsAndErrorsBeforeFlushingData()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValues($this->writer, array(
            'errors' => array('some_errors'),
            'stats' => array(
                ContainerInterface::TYPE_INSERT => 122,
                ContainerInterface::TYPE_UPDATE => 123,
                ContainerInterface::TYPE_DELETE => 124
            )
        ));
        
        $this->writer->flush();
        $this->assertSame(array(), $this->writer->getErrors());
        $this->assertSame(
            array(
                ContainerInterface::TYPE_INSERT => 0,
                ContainerInterface::TYPE_UPDATE => 0,
                ContainerInterface::TYPE_DELETE => 0
            ), 
            $this->writer->getStats()
        );
    }

    /**
     * @mockContainer
     * @mockMethod flushInsert
     * @mockMethod flushDelete
     * @mockMethod flushUpdate
     */
    public function testItFlushesPrimaryAndSecondaryQueueWithinOneTransaction()
    {
        $this->writer->expects($this->exactly(2))
            ->method('flushDelete')
            ->id('delete')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY),
                array(ContainerInterface::QUEUE_SECONDARY)                
            )
            ->willReturnSelf()
        ;

        $this->writer->expects($this->exactly(2))
            ->method('flushInsert')
            ->id('insert')
            ->after('delete')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY),
                array(ContainerInterface::QUEUE_SECONDARY)
            )
            ->willReturnSelf()
        ;

        $this->writer->expects($this->exactly(2))
            ->method('flushUpdate')
            ->id('update')
            ->after('insert')
            ->withConsecutive(
                array(ContainerInterface::QUEUE_PRIMARY),
                array(ContainerInterface::QUEUE_SECONDARY)
            )
            ->willReturnSelf()
        ;
        $this->adapterMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturnSelf();
        $this->adapterMock->expects($this->once())
            ->method('commit')
            ->willReturnSelf();
        
        $this->assertSame($this->writer, $this->writer->flush());
    }

    /**
     * @mockContainer
     * @mockMethod flushInsert
     * @mockMethod flushDelete
     * @mockMethod flushUpdate
     */
    public function testItRollbacksTransactionIfAnyErrorOccursDuringFlush()
    {
        $this->writer->expects($this->once())
            ->method('flushDelete')
            ->with(ContainerInterface::QUEUE_PRIMARY)
            ->willThrowException(new Exception('Test'));
        ;

        $this->writer->expects($this->never())
            ->method('flushInsert')
        ;

        $this->writer->expects($this->never())
            ->method('flushUpdate')
        ;
        $this->adapterMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturnSelf();

        $this->adapterMock->expects($this->once())
            ->method('rollback')
            ->willReturnSelf();
        
        $this->adapterMock->expects($this->never())
            ->method('commit');


        $this->setExpectedException('Exception', 'Test');
        $this->writer->flush();
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
