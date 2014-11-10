<?php

use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
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
        $mockWriter = TestUtil::getAnnotationByNameFromClass(__CLASS__, 'mockWriter', 'method', $this->getName());
        
        if ($mockResolver) {
            $this->resolverMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_ResolverInterface');
        }
        
        if ($mockWriter) {
            $constructorArgs = array(
                $this->adapterMock,
                $this->schemaMock,
                $this->resolverMock
            );
            
            $this->writer = $this->getMock('EcomDev_Fixture_Db_Writer', $mockWriter, $constructorArgs);
        } else {
            $this->writer = new Writer($this->adapterMock, $this->schemaMock, $this->resolverMock);
        }
    }

    /**
     * @mockResolver
     */
    public function testItHasRequiredAttributes()
    {
        $this->writer = new Writer($this->adapterMock, $this->schemaMock, $this->resolverMock);
        
        $this->assertObjectHasAttribute('adapter', $this->writer);
        $this->assertObjectHasAttribute('schema', $this->writer);
        $this->assertObjectHasAttribute('resolver', $this->writer);
        $this->assertObjectHasAttribute('schedule', $this->writer);
    }

    /**
     * @mockResolver
     */
    public function testItStoresPassedDependenciesProperty()
    {
        $this->assertAttributeSame($this->adapterMock, 'adapter', $this->writer);
        $this->assertAttributeSame($this->schemaMock, 'schema', $this->writer);
        $this->assertAttributeSame($this->resolverMock, 'resolver', $this->writer);
        
        $this->assertEquals($this->adapterMock, $this->writer->getAdapter());
        $this->assertEquals($this->schemaMock, $this->writer->getSchema());
        $this->assertEquals($this->resolverMock, $this->writer->getResolver());
    }

    public function testItSetsResolverToDefaultOneIfOtherIsNotSpecified()
    {
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Db_Resolver', 'resolver', $this->writer);
    }

    /**
     * @mockResolver
     */
    public function testItMapsRowIfCanMapReturnsTrueButNotOtherwise()
    {
        $this->resolverMock->expects($this->exactly(2))
            ->method('canMapRow')
            ->withConsecutive(array('table1'), array('table2'))
            ->willReturnOnConsecutiveCalls(true, false);
        
        $map = new Map('table1', array('field' => 'value'));
        $this->resolverMock->expects($this->once())
            ->method('mapRow')
            ->with('table1', array('field' => 'value', 'field2' => 'value2'))
            ->willReturn($map);

        $this->assertEquals(
            $map, 
            $this->writer->registerRow('table1', array('field' => 'value', 'field2' => 'value2'))
        );
        
        $this->assertEquals(
            false, 
            $this->writer->registerRow('table2', array('field' => 'value', 'field2' => 'value2'))
        );
    }
    
    public function testItPreparesRowForADatabase()
    {
        $methods = array('getRecommendedValue');
        $columns = array();
        $columns['field1'] = $this->mockedColumn(array('field1', Column::TYPE_INTEGER, Column::OPTION_PRIMARY), $methods);
        $columns['field1']
                ->expects($this->never())
                ->method('getRecommendedValue');
        $columns['field2'] = $this->mockedColumn(array('field2', Column::TYPE_INTEGER), $methods);
        $columns['field2']->expects($this->once())
                ->method('getRecommendedValue')
                ->with(2)
                ->willReturn('2');
        $columns['field3'] = $this->mockedColumn(array('field3', Column::TYPE_INTEGER), $methods);
        $columns['field3']->expects($this->once())
                ->method('getRecommendedValue')
                ->with(3)
                ->willReturn('3');
        $columns['field4'] = $this->mockedColumn(array('field4', Column::TYPE_TEXT), $methods);
        $columns['field4']->expects($this->once())
                ->method('getRecommendedValue')
                ->with($this->isNull())
                ->willReturn('text');

        $columns['field5'] = $this->mockedColumn(array('field5', Column::TYPE_TEXT), $methods);
        $columns['field5']->expects($this->never())
            ->method('getRecommendedValue');

        $columns['field6'] = $this->mockedColumn(array('field6', Column::TYPE_TEXT), $methods);
        $columns['field6']->expects($this->never())
            ->method('getRecommendedValue');

        $table = $this->mockedTable(
            'table1',
            $columns
        );

        $idMap = new Map('table1', array('field2' => '2'));
        $this->assertSame(
            array(
                'field1' => $idMap,
                'field2' => '2',
                'field3' => '3',
                'field4' => 'text',
                'field5' => serialize(array('5')),
                'field6' => json_encode(array('6')),
            ),
            $this->writer->processRow(
                $table, 
                array(
                    'field1' => $idMap,
                    'field2' => 2, 
                    'field3' => 3,
                    'field5' => array('serialized' => array('5')),
                    'field6' => array('json' => array('6'))
                )
            )
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid value supplied for "field1" in "table1"
     */
    public function testItThrowsAnExceptionIfColumnValueIsInvalidArray()
    {
        $columns = array();
        $columns['field1'] = $this->mockedColumn(
            array('field1', Column::TYPE_TEXT), 
            array('getRecommendedValue')
        );
        $columns['field1']
            ->expects($this->never())
            ->method('getRecommendedValue');
        
        $table = $this->mockedTable(
            'table1',
            $columns
        );


        $this->writer->processRow(
            $table,
            array(
                'field1' => array('something'),
            )
        );
    }

    public function testItKeepsMapIdentifierEvenIfItIsNotAPrimaryKey()
    {
        $columns = array();
        $columns['field1'] = $this->mockedColumn(
            array('field1', Column::TYPE_TEXT),
            array('getRecommendedValue')
        );
        $columns['field1']
            ->expects($this->never())
            ->method('getRecommendedValue');
        
        $table = $this->mockedTable(
            'table1',
            $columns
        );

        $idMap = new Map('table1', array('field2' => '2'));
        
        $this->assertSame(
            array(
                'field1' => $idMap
            ),
            $this->writer->processRow(
                $table,
                array(
                    'field1' => $idMap
                )
            )
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The primary key for "table1" is required, since it is not autoincrement based.
     */
    public function testItRisesAnErrorIfPrimaryKeyIsNotSpecifiedAndItIsNotAutoincrementField()
    {
        $columns = array();
        $columns['field1'] = $this->mockedColumn(
            array('field1', Column::TYPE_VARCHAR, Column::OPTION_PRIMARY),
            array('getRecommendedValue')
        );
        $columns['field1']
            ->expects($this->never())
            ->method('getRecommendedValue');
        $columns['field2'] = $this->mockedColumn(
            array('field2', Column::TYPE_INTEGER),
            array('getRecommendedValue')
        );
        $columns['field2']
            ->expects($this->never())
            ->method('getRecommendedValue');
        
        $table = $this->mockedTable(
            'table1',
            $columns
        );

        $this->writer->processRow(
            $table,
            array(
                'field2' => 2
            )
        );
    }
    
    
    /**
     * @param $tableName
     * @param Column[] $columns
     * @param Key[] $keys
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockedTable($tableName, $columns = array(), $keys = array())
    {
        $table = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Schema_TableInterface');
        $table->expects($this->any())
            ->method('getName')
            ->willReturn($tableName);
        
        $columnsForMock = array();
        foreach ($columns as $column) {
            $columnsForMock[$column->getName()] = $column;
        }
        
        $table->expects($this->any())
            ->method('getColumns')
            ->willReturn($columnsForMock);
        
        $keysForMock = array();
        $primaryKey = false;
        foreach ($keys as $key) {
            $keysForMock[$key->getName()] = $key;
            if ($key->getType() === Key::TYPE_PRIMARY) {
                $primaryKey = $key;
            }
        }
        
        $table->expects($this->any())
            ->method('getKeys')
            ->willReturn($keysForMock);
        
        $table->expects($this->any())
            ->method('getPrimaryKey')
            ->willReturn($primaryKey);
        
        return $table;
    }

    /**
     * Mocked column instance
     * 
     * @param array $args
     * @param array $methods
     * @return EcomDev_Fixture_Db_Schema_Column|PHPUnit_Framework_MockObject_MockObject
     */
    public function mockedColumn($args, $methods = array())
    {
        return $this->getMock(
            'EcomDev_Fixture_Db_Schema_Column', $methods, array(
                $args[0], $args[1], null, null, null, isset($args[2]) ? $args[2] : 0
            )
        );
    }
}
