<?php

use EcomDev_Fixture_Db_Resolver as Resolver;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface; 
use EcomDev_Fixture_Db_Schema_Table as Table;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Fixture_Db_Schema_Key as Key;
use EcomDev_Utils_Reflection as ReflectionUtil;

class EcomDev_FixtureTest_Test_Lib_Db_ResolverTest 
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

    protected function setUp()
    {
        $this->adapterMock = $this->getMockBuilder('Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        
        $this->adapterMock->expects($this->any())
            ->method('quoteIdentifier')
            ->willReturnCallback(function ($identifier) {
                return '`' . $identifier . '`';
            });
        
        $this->adapterMock->expects($this->any())
            ->method('quoteInto')
            ->willReturnCallback(function ($placeholder, $value) {
                if (is_array($value)) {
                    $value = implode('\',\'', array_map('addslashes', $value));
                } else {
                    $value = addslashes($value);
                }
                
                return str_replace('?', '\'' . $value . '\'', $placeholder);
            });
        
        $this->adapterMock->expects($this->any())
            ->method('getConcatSql')
            ->willReturnCallback(function (array $data, $separator = null) {
                $format = empty($separator) ? 'CONCAT(%s)' : "CONCAT_WS('{$separator}', %s)";
                return new Zend_Db_Expr(sprintf($format, implode(', ', $data)));
            });
        
        
        $this->schemaMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_SchemaInterface');
    }
    
    public function testItHasRequiredAttributes()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $this->assertObjectHasAttribute('adapter', $resolver);
        $this->assertObjectHasAttribute('schema', $resolver);
        $this->assertObjectHasAttribute('container', $resolver);
    }
    
    public function testItStoresAdapterIntoAdapterProperty()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $this->assertAttributeSame($this->adapterMock, 'adapter', $resolver);
        $this->assertAttributeSame($this->schemaMock, 'schema', $resolver);
    }

    public function testItSetsContainerToDefaultContainerIfOtherIsNotSpecified()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Db_Resolver_Container', 'container', $resolver);
    }
    
    public function testItCallsMapMethodOnContainerInstance()
    {
        $containerMock = $this->getMockForAbstractClass(
            'EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface'
        );
        
        $resolver = new Resolver($this->adapterMock, $this->schemaMock, $containerMock);
        
        $containerMock->expects($this->once())
            ->method('map')
            ->with('table1', 'value1')
            ->willReturn('some_value');
        
        $this->assertSame('some_value', $resolver->map('table1', 'value1'));
    }
    
    public function testItCallsCanMapRowMethodOnContainerInstance()
    {
        $containerMock = $this->getMockForAbstractClass(
            'EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface'
        );

        $resolver = new Resolver($this->adapterMock, $this->schemaMock, $containerMock);

        $containerMock->expects($this->exactly(2))
            ->method('canMapRow')
            ->withConsecutive(array('table1'), array('table2'))
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($resolver->canMapRow('table1'));
        $this->assertFalse($resolver->canMapRow('table2'));
    }

    public function testItCallsMapRowMethodOnContainerInstance()
    {
        $containerMock = $this->getMockForAbstractClass(
            'EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface'
        );

        $resolver = new Resolver($this->adapterMock, $this->schemaMock, $containerMock);

        $containerMock->expects($this->once())
            ->method('mapRow')
            ->with('table1', array('field1' => 'value1'))
            ->willReturn('some_value');

        $this->assertSame('some_value', $resolver->mapRow('table1', array('field1' => 'value1')));
    }

    public function testItReturnsContainer()
    {
        $containerMock = $this->getMockForAbstractClass(
            'EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface'
        );
        $resolver = new Resolver($this->adapterMock, $this->schemaMock, $containerMock);
        $this->assertSame($containerMock, $resolver->getContainer());
    }

    public function testItReturnsAdapter()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $this->assertSame($this->adapterMock, $resolver->getAdapter());
    }
    
    public function testItResetsContainer()
    {
        $containerMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Db_Resolver_ContainerInterface');
        $resolver = new Resolver($this->adapterMock, $this->schemaMock, $containerMock);
        $containerMock->expects($this->once())
            ->method('reset');
        
        $this->assertSame($resolver, $resolver->reset());
    }
    
    public function testItDoesNotResolveAnyItemsForTableIfContainer()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);

        $this->adapterMock->expects($this->never())
            ->method('select');
            
        $this->assertSame($resolver, $resolver->resolve('table1'));        
    }
    
    public function testItResolvesSingleItem()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $map = $resolver->map('table1', array('field1' => 'value'));

        $this->assertFalse($map->isResolved());
        
        $actualSelectObject = $this->stubTableSchema('table1',  array('id', 'field1'), 'id')
            ->stubResolveResponse(array('value' => '123'));
        
        $expectedSelectObject = new Varien_Db_Select($this->adapterMock);
        $expectedSelectObject->from('table1', array('field1', 'id'));
        $expectedSelectObject->where('`field1` IN(?)', array('value'));
        
        $this->assertSame($resolver, $resolver->resolve('table1'));
        $this->assertTrue($map->isResolved());
        $this->assertEquals('123', $map->getValue());
        $this->assertEquals($expectedSelectObject, $actualSelectObject);
    }

    public function testItResolvesMultipleEntriesWithOneQuery()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $firstMap = $resolver->map('table1', array('field1' => 'value1'));
        $secondMap = $resolver->map('table1', array('field1' => 'value2'));
        
        $expectedSelectObject = new Varien_Db_Select($this->adapterMock);
        $expectedSelectObject->from('table1', array('field1', 'id'));
        $expectedSelectObject->where('`field1` IN(?)', array('value1', 'value2'));

        $actualSelectObject = $this->stubTableSchema('table1', array('id', 'field1'), 'id')
            ->stubResolveResponse(array(
                'value1' => '121',
                'value2' => '122'
            ));

        $this->assertSame($resolver, $resolver->resolve('table1'));
        $this->assertTrue($firstMap->isResolved());
        $this->assertTrue($secondMap->isResolved());
        $this->assertEquals('121', $firstMap->getValue());
        $this->assertEquals('122', $secondMap->getValue());
        $this->assertEquals($expectedSelectObject, $actualSelectObject);
    }

    public function testItResolvesMultipleEntriesByDifferentKeys()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $firstMap = $resolver->map('table1', array('field1' => 'value1'));
        $secondMap = $resolver->map('table1', array('field2' => 'value2'));

        $expectedSelectObjects = array();
        $select = new Varien_Db_Select($this->adapterMock);
        $select->from('table1', array('field1', 'id'));
        $select->where('`field1` IN(?)', array('value1'));
        $expectedSelectObjects[] = $select;
        $select = new Varien_Db_Select($this->adapterMock);
        $select->from('table1', array('field2', 'id'));
        $select->where('`field2` IN(?)', array('value2'));
        $expectedSelectObjects[] = $select;

        $actualSelectObject = $this->stubTableSchema('table1', array('id', 'field1', 'field2'), 'id')
            ->stubResolveResponse(
                array(
                    array('value1' => '121'),
                    array('value2' => '122')
                ),
                2
            );

        $this->assertSame($resolver, $resolver->resolve('table1'));
        $this->assertTrue($firstMap->isResolved());
        $this->assertTrue($secondMap->isResolved());
        $this->assertEquals('121', $firstMap->getValue());
        $this->assertEquals('122', $secondMap->getValue());
        $this->assertEquals($expectedSelectObjects, $actualSelectObject);
    }

    public function testItResolvesCombinedKeyCondition()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $firstMap = $resolver->map('table1', array('field1' => 'value1.1', 'field2' => 'value1.2'));
        $secondMap = $resolver->map('table1', array('field1' => 'value2.1', 'field2' => 'value2.2'));
        
        $expectedSelectObject = new Varien_Db_Select($this->adapterMock);
        $expectedSelectObject->from('table1', array('CONCAT_WS(\'|\', `field1`, `field2`)', 'id'));
        $expectedSelectObject->where(
            'CONCAT_WS(\'|\', `field1`, `field2`) IN(?)', 
            array('value1.1|value1.2', 'value2.1|value2.2')
        );

        $actualSelectObject = $this->stubTableSchema('table1', array('id', 'field1', 'field2'), 'id')
            ->stubResolveResponse(
                array(
                    'value1.1|value1.2' => '121',
                    'value2.1|value2.2' => '122'
                )
            );

        $this->assertSame($resolver, $resolver->resolve('table1'));
        $this->assertTrue($firstMap->isResolved());
        $this->assertTrue($secondMap->isResolved());
        $this->assertEquals('121', $firstMap->getValue());
        $this->assertEquals('122', $secondMap->getValue());
        $this->assertEquals($expectedSelectObject, $actualSelectObject);
    }

    public function testItImplementsNotifierAwareInterface()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $this->assertInstanceOf('EcomDev_Fixture_Contract_Utility_NotifierAwareInterface', $resolver);
        $this->assertObjectHasAttribute('notifiers', $resolver);
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Utility_Notifier_Container', 'notifiers', $resolver);
    }
    
    public function testItIsProxiesCallsToNotifier()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $notifierMock = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface'); 
        $notifierContainerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        ReflectionUtil::setRestrictedPropertyValue($resolver, 'notifiers', $notifierContainerMock);
        $notifierContainerMock->expects($this->once())
            ->method('add')
            ->with($notifierMock)
            ->willReturnSelf();
        
        $this->assertSame($resolver, $resolver->addNotifier($notifierMock));
        
        $notifierContainerMock->expects($this->once())
            ->method('remove')
            ->willReturnSelf()
            ;

        $this->assertSame($resolver, $resolver->removeNotifier($notifierMock));

        $notifierContainerMock->expects($this->once())
            ->method('items')
            ->willReturn(array($notifierMock))
        ;

        $this->assertSame(array($notifierMock), $resolver->getNotifiers());
    }
    
    public function testItNotifiesAboutTableResolveOperation()
    {
        $resolver = new Resolver($this->adapterMock, $this->schemaMock);
        $firstMap = $resolver->map('table1', array('field1' => 'value1.1'));
        $secondMap = $resolver->map('table1', array('special_code' => 'unique_id'));
        $secondMap->setTable('table2');
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        
        $expectedStdObject = new stdClass();
        $expectedStdObject->resolveTable = 'table1';
        $expectedStdObject->mapTable = 'table2';
        $expectedStdObject->primaryColumn = '';
        $expectedStdObject->customCondition = array();
        
        $notifier->expects($this->once())
            ->method('notify')
            ->with($this->isInstanceOf('EcomDev_Fixture_Db_Resolver'), 'resolve_table', $this->equalTo($expectedStdObject))
            ->willReturnCallback(function ($resolver, $operation, $data) use ($expectedStdObject) {
                $data->primaryColumn = 'external_id';
                $data->customCondition['type'] = 'value';
                // Fix comparator of the object arguments
                $expectedStdObject->primaryColumn = 'external_id';
                $expectedStdObject->customCondition['type'] = 'value';
            });
        
        $resolver->addNotifier($notifier);
        $actualSelectObjects = $this->stubTableSchema(
                array('table1', 'table2'),
                array(
                    array('id', 'field1', 'field2'),
                    array('special_code', 'type', 'external_id')
                ),
                array('id', false)
            )
            ->stubResolveResponse(
                array(
                    array('value1.1' => '121'),
                    array('unique_id' => '122')
                ),
                2
            );

        $expectedSelectObjects = array();
        $select = new Varien_Db_Select($this->adapterMock);
        $select->from('table1', array('field1', 'id'));
        $select->where('`field1` IN(?)', array('value1.1'));
        $expectedSelectObjects[] = $select;
        $select = new Varien_Db_Select($this->adapterMock);
        $select->from('table2', array('special_code', 'external_id'));
        $select->where('`special_code` IN(?)', array('unique_id'));
        $select->where('`type` = ?', 'value');
        $expectedSelectObjects[] = $select;

        $this->assertSame($resolver, $resolver->resolve('table1'));
        $this->assertTrue($firstMap->isResolved());
        $this->assertTrue($secondMap->isResolved());
        $this->assertEquals('121', $firstMap->getValue());
        $this->assertEquals('122', $secondMap->getValue());
        $this->assertEquals($expectedSelectObjects, $actualSelectObjects);
    }

    /**
     * Stubs adapter calls to retrieve id map
     * 
     * @param array[] $data
     * @param int $times
     * @return Varien_Db_Select[]|Varien_Db_Select
     */
    protected function stubResolveResponse($data, $times = 1)
    {
        $selects = array();
        $matchArgs = array();

        if (!isset($data[0])) {
            $data = array($data);
        }
        
        for ($i = 0; $i < $times; $i ++) {
            $select = new Varien_Db_Select($this->adapterMock);
            $selects[] = $select;
            $matchArgs[] = array($this->identicalTo($select));
        }

        $this->adapterMock->expects($this->exactly($times))
            ->method('select')
            ->will(new PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($selects));

        
        $fetchPairsStub = $this->adapterMock->expects($this->exactly($times))
            ->method('fetchPairs')
            ->will(new PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($data));
        
        call_user_func_array(array($fetchPairsStub, 'withConsecutive'), $matchArgs);
        
        if (count($selects) == 1) {
            return current($selects);
        }
        
        return $selects;
    }

    /**
     * Stubs getTable call on schema
     *
     * @param string $tableName
     * @param array $columnNames
     * @param bool|string $primaryColumn
     * @return $this
     */
    protected function stubTableSchema($tableName, array $columnNames, $primaryColumn = false)
    {
        if (!is_array($tableName)) {
            $this->schemaMock->expects($this->once())
                ->method('getTableInfo')
                ->with($tableName)
                ->willReturn($this->generateTable($tableName, $columnNames, $primaryColumn));
        } else {
            $matcher = $this->schemaMock->expects($this->exactly(count($tableName)))
                ->method('getTableInfo');
            
            $consecutiveWith = array();
            $consecutiveReturn = array();
            foreach ($tableName as $index => $table) {
                $consecutiveWith[] = array($table); 
                $consecutiveReturn[] = $this->generateTable($table, $columnNames[$index], $primaryColumn[$index]);
            }
            
            call_user_func_array(array($matcher, 'withConsecutive'), $consecutiveWith);
            call_user_func_array(array($matcher, 'willReturnOnConsecutiveCalls'), $consecutiveReturn);
        }
        
        return $this;
    }
    
    
    /**
     * Generates a new table object
     * 
     * @param string $tableName
     * @param array $columnNames
     * @param bool|string $primaryColumn
     * @return Table
     */
    protected function generateTable($tableName, array $columnNames, $primaryColumn = false)
    {
        
        $columns = array();
        $keys = array();
        foreach ($columnNames as $columnName) {
            $column = new Column(
                $columnName, 
                Column::TYPE_INTEGER, 
                null,
                null,
                null,
                $primaryColumn === $columnName ? Column::OPTION_PRIMARY : 0
            );
            $columns[$column->getName()] = $column;
        }
        
        if ($primaryColumn) {
            $keys['PRIMARY'] = new Key('PRIMARY', array($primaryColumn), Key::TYPE_PRIMARY);
        }

        $table = new Table($tableName, $columns, $keys);
        
        return $table;
    }
}
