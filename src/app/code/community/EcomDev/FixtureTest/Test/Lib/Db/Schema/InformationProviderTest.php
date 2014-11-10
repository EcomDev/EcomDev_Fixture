<?php

use EcomDev_Fixture_Db_Schema_InformationProvider as InformationProvider;
use EcomDev_Utils_Reflection as ReflectionUtil;

/**
 * Test case for information provider class
 */
class EcomDev_FixtureTest_Test_Lib_Db_Schema_InformationProviderTest extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    const CLASS_NAME = 'EcomDev_Fixture_Db_Schema_InformationProvider';

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql|PHPUnit_Framework_MockObject_MockObject
     */
    protected $adapterMock;
        
    protected function setUp()
    {
        $this->adapterMock = $this->getMockBuilder('Varien_Db_Adapter_Pdo_Mysql')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $this->adapterMock->expects($this->any())
            ->method('getConfig')
            ->willReturn(array('dbname' => 'magento'))
        ;
    }

    public function testItHasRequiredClassAttributes()
    {
        $this->assertTrue(class_exists(self::CLASS_NAME));
        $attributes = array('tableNames', 'columns', 'indexes', 'foreignKeys', 'loaded');
        
        foreach ($attributes as $attribute) {
            $this->assertClassHasAttribute($attribute, self::CLASS_NAME);
        }
    }

    public function testItSetsInitialPropertyValues()
    {
        $infoProvider = new InformationProvider($this->adapterMock);
        
        $this->assertAttributeEquals($this->adapterMock, 'adapter', $infoProvider);
        
        $attributes = array('tableNames', 'columns', 'indexes', 'foreignKeys');
        
        foreach ($attributes as $attribute) {
            $this->assertAttributeInternalType('array', $attribute, $infoProvider);
            $this->assertAttributeEmpty($attribute, $infoProvider);
        }
        
        $this->assertAttributeEquals(false, 'loaded', $infoProvider);
    }
    
    public function testItDoesNotLoadDatabaseInformationIfLoadedFlagEqualsTrue()
    {
        $infoProvider = new InformationProvider($this->adapterMock);
        
        $this->adapterMock->expects($this->never())
            ->method('listTables');

        $this->adapterMock->expects($this->never())
            ->method('query');

        ReflectionUtil::setRestrictedPropertyValue($infoProvider, 'loaded', true);
        
        $this->assertSame($infoProvider, $infoProvider->load());
    }

    public function testItLoadInfoFromDatabaseWithEmptyData()
    {
        $infoProvider = new InformationProvider($this->adapterMock);

        $this->adapterMock->expects($this->once())
            ->method('listTables')
            ->willReturn(array());

        $this->adapterMock->expects($this->exactly(3))
            ->method('select')
            ->willReturnCallback(function () {
                return new Varien_Db_Select($this->adapterMock);
            });
        
        $this->adapterMock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                array($this->expectedDbSelect('columns')),
                array($this->expectedDbSelect('statistics')),
                array($this->expectedForeignKeySelect())
            )            
            ->willReturn(array());

        $this->assertSame($infoProvider, $infoProvider->load());
        $this->assertAttributeEquals(true, 'loaded', $infoProvider);
    }

    public function testItTransformsDataOnLoad()
    {
        $infoProvider = new InformationProvider($this->adapterMock);

        $this->adapterMock->expects($this->once())
            ->method('listTables')
            ->willReturn(
                array(
                    'sales_flat_order', 
                    'sales_flat_order_item'
                )
            );

        $this->adapterMock->expects($this->exactly(3))
            ->method('select')
            ->willReturnCallback(function () {
                return new Varien_Db_Select($this->adapterMock);
            });

        $this->adapterMock->expects($this->exactly(3))
            ->method('fetchAll')
            ->withConsecutive(
                array($this->expectedDbSelect('columns')),
                array($this->expectedDbSelect('statistics')),
                array($this->expectedForeignKeySelect())
            )
            ->willReturnOnConsecutiveCalls(
                $this->loadDataFile('Columns'),
                $this->loadDataFile('TableStatistics'),
                $this->loadDataFile('TableConstraints')
            );

        $this->assertSame($infoProvider, $infoProvider->load());
        
        $expectedData = $this->loadDataFile('ExpectedData');
        $this->assertAttributeEquals(array_keys($expectedData['columns']), 'tableNames', $infoProvider);
        $this->assertAttributeEquals($expectedData['columns'], 'columns', $infoProvider);
        $this->assertAttributeEquals($expectedData['indexes'], 'indexes', $infoProvider);
        $this->assertAttributeEquals($expectedData['foreignKeys'], 'foreignKeys', $infoProvider);
    }
    
    public function testItInvokesLoadWhenGetTableNamesIsCalledAndReturnsStoredProperty()
    {
        $infoProvider = $this->getMockBuilder(self::CLASS_NAME)
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();
        
        $infoProvider->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        
        ReflectionUtil::setRestrictedPropertyValue(
            $infoProvider, 
            'tableNames', 
            array('dummy_tablename')
        );
        
        $this->assertEquals(array('dummy_tablename'), $infoProvider->getTableNames());
    }

    public function testItInvokesLoadWhenGetColumnsIsCalledAndReturnsMappedPropertyValue()
    {
        $infoProvider = $this->getMockBuilder(self::CLASS_NAME)
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        $infoProvider->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        ReflectionUtil::setRestrictedPropertyValue(
            $infoProvider, 
            'columns', 
            array('dummy_tablename' => array('dummy' => 'data'))
        );

        $this->assertEquals(array('dummy' => 'data'), $infoProvider->getColumns('dummy_tablename'));
    }

    public function testItInvokesLoadWhenGetIndexesIsCalledAndReturnsMappedPropertyValue()
    {
        $infoProvider = $this->getMockBuilder(self::CLASS_NAME)
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        $infoProvider->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        ReflectionUtil::setRestrictedPropertyValue(
            $infoProvider, 
            'indexes',
            array('dummy_tablename' => array('dummy' => 'data'))
        );

        $this->assertEquals(array('dummy' => 'data'), $infoProvider->getIndexes('dummy_tablename'));
    }

    public function testItInvokesLoadWhenGetForeignKeysIsCalledAndReturnsMappedPropertyValue()
    {
        $infoProvider = $this->getMockBuilder(self::CLASS_NAME)
            ->disableOriginalConstructor()
            ->setMethods(array('load'))
            ->getMock();

        $infoProvider->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        ReflectionUtil::setRestrictedPropertyValue(
            $infoProvider, 
            'foreignKeys', 
            array('dummy_tablename' => array('dummy' => 'data'))
        );

        $this->assertEquals(array('dummy' => 'data'), $infoProvider->getForeignKeys('dummy_tablename'));
    }
    
    public function testItResetsLoadedFlag()
    {
        $infoProvider = new InformationProvider($this->adapterMock);

        ReflectionUtil::setRestrictedPropertyValue($infoProvider, 'loaded', true);
        
        $infoProvider->reset();
        
        $this->assertAttributeEquals(false, 'loaded', $infoProvider);
    }

    /**
     * Returns an instance of expected foreign 
     * 
     * @return Varien_Db_Select
     */
    protected function expectedForeignKeySelect()
    {
        $select = $this->expectedDbSelect(
            array('constraint' => 'referential_constraints'), 
            'constraint.CONSTRAINT_SCHEMA'
        );
        $select->join(
            array('column' => 'key_column_usage'),
            'column.CONSTRAINT_SCHEMA = constraint.CONSTRAINT_SCHEMA'
            . ' AND column.CONSTRAINT_NAME = constraint.CONSTRAINT_NAME'
            . ' AND column.TABLE_NAME = constraint.TABLE_NAME'
            . ' AND column.REFERENCED_TABLE_NAME = constraint.REFERENCED_TABLE_NAME',
            array(
                'COLUMN_NAME',
                'REFERENCED_COLUMN_NAME'
            ),
            'information_schema');
        
        return $select;
    }

    /**
     * Expected DB select creation method
     * 
     * @param array|string $informationTableName
     * @param string $conditionField
     * @param string $database
     * @return Varien_Db_Select
     */
    protected function expectedDbSelect($informationTableName, $conditionField = 'TABLE_SCHEMA', $database = 'magento')
    {
        $select = new Varien_Db_Select($this->adapterMock);
        $select->from($informationTableName, '*', 'information_schema')
            ->where($conditionField . ' = ?', $database);
        
        return $select;
    }
    
}