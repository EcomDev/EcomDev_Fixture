<?php
use EcomDev_Fixture_Db_Schema_Key as Key;

class EcomDev_FixtureTest_Test_Lib_Db_Schema_KeyTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testItHasRequiredAttributes()
    {
        $this->assertTrue(class_exists('EcomDev_Fixture_Db_Schema_Key'));
        $this->assertClassHasAttribute('name', 'EcomDev_Fixture_Db_Schema_Key');
        $this->assertClassHasAttribute('columns', 'EcomDev_Fixture_Db_Schema_Key');
        $this->assertClassHasAttribute('type', 'EcomDev_Fixture_Db_Schema_Key');
    }

    /**
     * Data provider
     * 
     * @return array[]
     */
    public function dataProviderConstructorArguments()
    {
        return array(
            'one_column' => array('someName', array('columnName')),
            'one_column_2' => array('PRIMARY', array('columnName2'), Key::TYPE_PRIMARY),
            'one_column_3' => array('unique_key', array('columnName', 'columnName2'), Key::TYPE_UNIQUE),
        );
    }

    /**
     * @param string $name
     * @param string[] $columns 
     * @param string|null $type
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectName($name, $columns, $type = null)
    {
        $key = $this->_createKey($name, $columns, $type);
        $this->assertEquals($name, $key->getName());
    }

    /**
     * @param string $name
     * @param string[] $columns
     * @param string|null $type
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectColumns($name, $columns, $type = null)
    {
        $key = $this->_createKey($name, $columns, $type);
        $this->assertEquals($columns, $key->getColumns());
    }

    /**
     * @param string $name
     * @param string[] $columns
     * @param string|null $type
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectType($name, $columns, $type = null)
    {
        $key = $this->_createKey($name, $columns, $type);
        
        if ($type === null) {
            $type = Key::TYPE_INDEX;
        }
        
        $this->assertEquals($type, $key->getType());
    }
    
    /**
     * Create of the key 
     * 
     * @param string $name
     * @param array $columns
     * @param string|null $type
     * @return EcomDev_Fixture_Db_Schema_Key
     */
    protected function _createKey($name, $columns, $type = null)
    {
        if ($type == null) {
            return new Key($name, $columns);
        }
        
        return new Key($name, $columns, $type);
    }
}
