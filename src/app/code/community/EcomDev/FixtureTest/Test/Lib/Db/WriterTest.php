<?php

use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_Writer_ContainerInterface as ContainerInterface;
use EcomDev_Fixture_Db_Writer as Writer;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Fixture_Db_Schema_Key as Key;
use EcomDev_Fixture_Db_Schema_ForeignKey as ForeignKey;
use EcomDev_Fixture_Db_Resolver_Map as Map;
use EcomDev_Fixture_Db_Map_Static as StaticMap;
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
     * @var Writer
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

        $mockResolver = TestUtil::getAnnotationByNameFromClass(__CLASS__, 'mockResolver', 'method', $this->getName());
        $mockContainer = TestUtil::getAnnotationByNameFromClass(__CLASS__, 'mockContainer', 'method', $this->getName());
        
        if ($mockResolver) {
            $this->resolverMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_ResolverInterface');
        }
        
        if ($mockContainer) {
            $this->containerMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Writer_ContainerInterface');
        }
        
        $this->writer = new Writer($this->adapterMock, $this->schemaMock, $this->resolverMock, $this->containerMock);
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
    
}
