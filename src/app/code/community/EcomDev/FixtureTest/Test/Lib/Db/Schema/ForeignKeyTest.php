<?php

use EcomDev_Fixture_Db_Schema_ForeignKey as ForeignKey;

class EcomDev_FixtureTest_Test_Lib_Db_Schema_ForeignKeyTest
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testItHasRequiredAttributes()
    {
        $this->assertTrue(class_exists('EcomDev_Fixture_Db_Schema_ForeignKey'));
        $this->assertClassHasAttribute('name', 'EcomDev_Fixture_Db_Schema_ForeignKey');
        $this->assertClassHasAttribute('columns', 'EcomDev_Fixture_Db_Schema_ForeignKey');
        $this->assertClassHasAttribute('referenceTable', 'EcomDev_Fixture_Db_Schema_ForeignKey');
        $this->assertClassHasAttribute('referenceColumns', 'EcomDev_Fixture_Db_Schema_ForeignKey');
        $this->assertClassHasAttribute('updateAction', 'EcomDev_Fixture_Db_Schema_ForeignKey');
        $this->assertClassHasAttribute('deleteAction', 'EcomDev_Fixture_Db_Schema_ForeignKey');
    }

    public function dataProviderConstructorArguments()
    {
        return array(
            'one_column' => array('someName', array('columnName'), 'someTable', array('columnName')),
            'two_column' => array('someName3', array('columnName', 'columnName2'), 'someTable', array('columnName', 'columnName2')),
            'two_column_action' => array('someName4', array('columnName', 'columnName2'), 'someTable', array('columnName', 'columnName2'), ForeignKey::ACTION_NO_ACTION, ForeignKey::ACTION_NO_ACTION)            
        );
    }

    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param null $onUpdate
     * @param null $onDelete
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectName($name, $columns, $referenceTable, $referenceColumns, $onUpdate = null, $onDelete = null)
    {
        $foreignKey = $this->_createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        $this->assertEquals($name, $foreignKey->getName());
    }

    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param null $onUpdate
     * @param null $onDelete
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectColumns($name, $columns, $referenceTable, $referenceColumns, $onUpdate = null, $onDelete = null)
    {
        $foreignKey = $this->_createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        $this->assertEquals($columns, $foreignKey->getColumns());
    }

    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param null $onUpdate
     * @param null $onDelete
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectReferenceTable($name, $columns, $referenceTable, $referenceColumns, $onUpdate = null, $onDelete = null)
    {
        $foreignKey = $this->_createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        $this->assertEquals($referenceTable, $foreignKey->getReferenceTable());
    }

    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param null $onUpdate
     * @param null $onDelete
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectReferenceColumns($name, $columns, $referenceTable, $referenceColumns, $onUpdate = null, $onDelete = null)
    {
        $foreignKey = $this->_createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        $this->assertEquals($referenceColumns, $foreignKey->getReferenceColumns());
    }

    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param null $onUpdate
     * @param null $onDelete
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectUpdateAction($name, $columns, $referenceTable, $referenceColumns, $onUpdate = null, $onDelete = null)
    {
        $foreignKey = $this->_createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        if ($onUpdate == null) {
            $onUpdate = ForeignKey::ACTION_CASCADE;
        }
        $this->assertEquals($onUpdate, $foreignKey->getUpdateAction());
    }


    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param null $onUpdate
     * @param null $onDelete
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectDeleteAction($name, $columns, $referenceTable, $referenceColumns, $onUpdate = null, $onDelete = null)
    {
        $foreignKey = $this->_createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        if ($onDelete == null) {
            $onDelete = ForeignKey::ACTION_CASCADE;
        }
        $this->assertEquals($onDelete, $foreignKey->getDeleteAction());
    }

    /**
     * @param $name
     * @param $columns
     * @param $referenceTable
     * @param $referenceColumns
     * @param $onUpdate
     * @param $onDelete
     * @return EcomDev_Fixture_Db_Schema_ForeignKey
     */
    protected function _createForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete)
    {
        if ($onUpdate === null && $onDelete == null) {
            return new ForeignKey($name, $columns, $referenceTable, $referenceColumns);
        } elseif ($onDelete == null) {
            return new ForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate);
        } else {
            return new ForeignKey($name, $columns, $referenceTable, $referenceColumns, $onUpdate, $onDelete);
        }
    }

}