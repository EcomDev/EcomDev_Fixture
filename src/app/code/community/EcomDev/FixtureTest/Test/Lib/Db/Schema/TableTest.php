<?php
use EcomDev_Fixture_Db_Schema_Table as Table;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Fixture_Db_Schema_Key as Key;
use EcomDev_Fixture_Db_Schema_ForeignKey as ForeignKey;

class EcomDev_FixtureTest_Test_Lib_Db_Schema_TableTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testItHasRequiredAttributes()
    {
        $this->assertTrue(class_exists('EcomDev_Fixture_Db_Schema_Table'));
        $this->assertClassHasAttribute('name', 'EcomDev_Fixture_Db_Schema_Table');
        $this->assertClassHasAttribute('columns', 'EcomDev_Fixture_Db_Schema_Table');
        $this->assertClassHasAttribute('keys', 'EcomDev_Fixture_Db_Schema_Table');
        $this->assertClassHasAttribute('foreignKeys', 'EcomDev_Fixture_Db_Schema_Table');
        $this->assertClassHasAttribute('parentTables', 'EcomDev_Fixture_Db_Schema_Table');
        $this->assertClassHasAttribute('childTables', 'EcomDev_Fixture_Db_Schema_Table');
    }
    
    public function dataProviderColumnMap()
    {
        return array(
            'column_one' => array(
                $columns = array(
                    'SCHEMA_NAME'      => null, 
                    'TABLE_NAME'       => 'table1',
                    'COLUMN_NAME'      => 'column_one',
                    'COLUMN_POSITION'  => 1,
                    'DATA_TYPE'        => Column::TYPE_TINYINT,
                    'DEFAULT'          => '1',
                    'NULLABLE'         => true,
                    'LENGTH'           => 3,
                    'SCALE'            => null,
                    'PRECISION'        => null,
                    'UNSIGNED'         => true,
                    'PRIMARY'          => true,
                    'PRIMARY_POSITION' => 0,
                    'IDENTITY'         => true
                ),
                new Column('column_one', Column::TYPE_TINYINT, '1', 3, null, Column::OPTION_UNSIGNED
                                                                             | Column::OPTION_NULLABLE
                                                                             | Column::OPTION_PRIMARY 
                                                                             | Column::OPTION_IDENTITY)
            ),
            'column_two' => array(
                array(
                    'SCHEMA_NAME'      => null,
                    'TABLE_NAME'       => 'table1',
                    'COLUMN_NAME'      => 'column_two',
                    'COLUMN_POSITION'  => 1,
                    'DATA_TYPE'        => Column::TYPE_INTEGER,
                    'DEFAULT'          => null,
                    'NULLABLE'         => false,
                    'LENGTH'           => 10,
                    'SCALE'            => null,
                    'PRECISION'        => null,
                    'UNSIGNED'         => false,
                    'PRIMARY'          => false,
                    'PRIMARY_POSITION' => 0,
                    'IDENTITY'         => false
                ),
                new Column('column_two', Column::TYPE_INTEGER, null, 10)
            ),
            'column_three' => array(
                array(
                    'SCHEMA_NAME'      => null,
                    'TABLE_NAME'       => 'table1',
                    'COLUMN_NAME'      => 'column_three',
                    'COLUMN_POSITION'  => 1,
                    'DATA_TYPE'        => Column::TYPE_DECIMAL,
                    'DEFAULT'          => '0.0000',
                    'NULLABLE'         => true,
                    'LENGTH'           => null,
                    'SCALE'            => 4,
                    'PRECISION'        => 12,
                    'UNSIGNED'         => false,
                    'PRIMARY'          => false,
                    'PRIMARY_POSITION' => 0,
                    'IDENTITY'         => false
                ),
                new Column('column_three', Column::TYPE_DECIMAL, '0.0000', 12, 4, Column::OPTION_NULLABLE)
            )
        );
    }

    /**
     * @param array $columnData
     * @param Column $expectedColumn
     * @dataProvider dataProviderColumnMap
     */
    public function testItCreatesNewColumn(array $columnData, Column $expectedColumn)
    {
        $table = new Table('table1');
        $column = $table->newColumn($columnData);
        $this->assertEquals($expectedColumn, $column);
    }

    /**
     * Data provider
     * 
     * @return array
     */
    public function dataProviderKeyMap()
    {
        return array(
            'key_one' => array(
                array(
                    'SCHEMA_NAME'   => '',
                    'TABLE_NAME'    => 'table1',
                    'KEY_NAME'      => 'IDX_KEYNAME',
                    'COLUMNS_LIST'  => array('column_two'),
                    'INDEX_TYPE'    => Key::TYPE_INDEX,
                    'INDEX_METHOD'  => 'BTREE',
                    'type'          => Key::TYPE_INDEX,
                    'fields'        => array('column_two')
                ),
                new Key('IDX_KEYNAME', array('column_two'), Key::TYPE_INDEX)
            ),
            'key_two' => array(
                array(
                    'SCHEMA_NAME'   => '',
                    'TABLE_NAME'    => 'table1',
                    'KEY_NAME'      => 'IDX_KEYNAME_TWO',
                    'COLUMNS_LIST'  => array('column_one'),
                    'INDEX_TYPE'    => Key::TYPE_UNIQUE,
                    'INDEX_METHOD'  => 'BTREE',
                    'type'          => Key::TYPE_UNIQUE,
                    'fields'        => array('column_one')
                ),
                new Key('IDX_KEYNAME_TWO', array('column_one'), Key::TYPE_UNIQUE)
            ),
            'key_three' => array(
                array(
                    'SCHEMA_NAME'   => '',
                    'TABLE_NAME'    => 'table1',
                    'KEY_NAME'      => 'PRIMARY',
                    'COLUMNS_LIST'  => array('column_three'),
                    'INDEX_TYPE'    => Key::TYPE_PRIMARY,
                    'INDEX_METHOD'  => 'BTREE',
                    'type'          => Key::TYPE_PRIMARY,
                    'fields'        => array('column_three')
                ),
                new Key('PRIMARY', array('column_three'), Key::TYPE_PRIMARY)
            )
        );
    }

    /**
     * @param array $keyData
     * @param Key $expectedKey
     * @dataProvider dataProviderKeyMap
     */
    public function testItCreatesNewKey(array $keyData, Key $expectedKey)
    {
        $table = new Table('table1');
        $key = $table->newKey($keyData);
        $this->assertEquals($expectedKey, $key);
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function dataProviderForeignKeyMap()
    {
        return array(
            'key_one' => array(
                array(
                    'FK_NAME'           => 'FK_TABLE1_NAME_ONE',
                    'SCHEMA_NAME'       => '',
                    'TABLE_NAME'        => 'table_name',
                    'COLUMN_NAME'       => 'column_one',
                    'REF_SHEMA_NAME'    => '',
                    'REF_TABLE_NAME'    => 'table_name_two',
                    'REF_COLUMN_NAME'   => 'column_one',
                    'ON_DELETE'         => ForeignKey::ACTION_NO_ACTION,
                    'ON_UPDATE'         => ''
                ),
                new ForeignKey(
                    'FK_TABLE1_NAME_ONE', 
                    array('column_one'),
                    'table_name_two', 
                    array('column_one'),
                    ForeignKey::ACTION_NO_ACTION,
                    ForeignKey::ACTION_NO_ACTION
                )
            ),
            'key_two' => array(
                array(
                    'FK_NAME'           => 'FK_TABLE1_NAME_TWO',
                    'SCHEMA_NAME'       => '',
                    'TABLE_NAME'        => 'table_name',
                    'COLUMN_NAME'       => 'column_two',
                    'REF_SHEMA_NAME'    => '',
                    'REF_TABLE_NAME'    => 'table_name_two',
                    'REF_COLUMN_NAME'   => 'column_two',
                    'ON_DELETE'         => ForeignKey::ACTION_SET_DEFAULT,
                    'ON_UPDATE'         => ForeignKey::ACTION_SET_DEFAULT
                ),
                new ForeignKey(
                    'FK_TABLE1_NAME_TWO',
                    array('column_two'),
                    'table_name_two',
                    array('column_two'),
                    ForeignKey::ACTION_SET_DEFAULT,
                    ForeignKey::ACTION_SET_DEFAULT
                )
            ),
            'key_three' => array(
                array(
                    'FK_NAME'           => 'FK_TABLE1_NAME_THREE',
                    'SCHEMA_NAME'       => '',
                    'TABLE_NAME'        => 'table_name',
                    'COLUMN_NAME'       => 'column_three',
                    'REF_SHEMA_NAME'    => '',
                    'REF_TABLE_NAME'    => 'table_name_two',
                    'REF_COLUMN_NAME'   => 'column_three',
                    'ON_DELETE'         => ForeignKey::ACTION_CASCADE,
                    'ON_UPDATE'         => ForeignKey::ACTION_CASCADE
                ),
                new ForeignKey(
                    'FK_TABLE1_NAME_THREE',
                    array('column_three'),
                    'table_name_two',
                    array('column_three'),
                    ForeignKey::ACTION_CASCADE,
                    ForeignKey::ACTION_CASCADE
                )
            )
        );
    }

    /**
     * @param array $foreignKeyData
     * @param ForeignKey $expectedKey
     * @dataProvider dataProviderForeignKeyMap
     */
    public function testItCreatesNewForeignKey(array $foreignKeyData, ForeignKey $expectedKey)
    {
        $table = new Table('table1');
        $foreignKey = $table->newForeignKey($foreignKeyData);
        $this->assertEquals($expectedKey, $foreignKey);
    }
    
    public function dataProviderTableConstructor()
    {
        return array(
            array(
                'table_one', 
                array(
                    'column_one' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_one',
                        'COLUMN_NAME'      => 'column_one',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_TINYINT,
                        'DEFAULT'          => '1',
                        'NULLABLE'         => true,
                        'LENGTH'           => 3,
                        'SCALE'            => null,
                        'PRECISION'        => null,
                        'UNSIGNED'         => true,
                        'PRIMARY'          => true,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => true
                    ),
                    'column_two' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_one',
                        'COLUMN_NAME'      => 'column_two',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_INTEGER,
                        'DEFAULT'          => null,
                        'NULLABLE'         => false,
                        'LENGTH'           => 10,
                        'SCALE'            => null,
                        'PRECISION'        => null,
                        'UNSIGNED'         => false,
                        'PRIMARY'          => false,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => false
                    ),
                    'column_three' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_one',
                        'COLUMN_NAME'      => 'column_three',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_DECIMAL,
                        'DEFAULT'          => '0.0000',
                        'NULLABLE'         => true,
                        'LENGTH'           => null,
                        'SCALE'            => 4,
                        'PRECISION'        => 12,
                        'UNSIGNED'         => false,
                        'PRIMARY'          => false,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => false
                    )
                ),
                array(
                    'key_one' => array(
                        'SCHEMA_NAME'   => '',
                        'TABLE_NAME'    => 'table_one',
                        'KEY_NAME'      => 'IDX_KEYNAME',
                        'COLUMNS_LIST'  => array('column_two'),
                        'INDEX_TYPE'    => Key::TYPE_INDEX,
                        'INDEX_METHOD'  => 'BTREE',
                        'type'          => Key::TYPE_INDEX,
                        'fields'        => array('column_two')
                    )
                ),
                array(
                    'key_one' => array(
                        'FK_NAME'           => 'FK_TABLE1_NAME_ONE',
                        'SCHEMA_NAME'       => '',
                        'TABLE_NAME'        => 'table_one',
                        'COLUMN_NAME'       => 'column_one',
                        'REF_SHEMA_NAME'    => '',
                        'REF_TABLE_NAME'    => 'table_name_two',
                        'REF_COLUMN_NAME'   => 'column_one',
                        'ON_DELETE'         => ForeignKey::ACTION_NO_ACTION,
                        'ON_UPDATE'         => ''
                     ),
                    'key_two' => array(
                        'FK_NAME'           => 'FK_TABLE1_NAME_TWO',
                        'SCHEMA_NAME'       => '',
                        'TABLE_NAME'        => 'table_one',
                        'COLUMN_NAME'       => 'column_two',
                        'REF_SHEMA_NAME'    => '',
                        'REF_TABLE_NAME'    => 'table_name_two',
                        'REF_COLUMN_NAME'   => 'column_two',
                        'ON_DELETE'         => ForeignKey::ACTION_SET_DEFAULT,
                        'ON_UPDATE'         => ForeignKey::ACTION_SET_DEFAULT
                    )
                ),
                array(
                    'name' => 'table_one',
                    'columns' => array(
                        'column_one' => new Column(
                            'column_one', Column::TYPE_TINYINT, '1', 3, null, 
                            Column::OPTION_NULLABLE | Column::OPTION_UNSIGNED 
                            | Column::OPTION_PRIMARY | Column::OPTION_IDENTITY
                        ),
                        'column_two' => new Column(
                            'column_two', Column::TYPE_INTEGER, null, 10
                        ),
                        'column_three' => new Column(
                            'column_three', Column::TYPE_DECIMAL, '0.0000', 12, 
                            4, Column::OPTION_NULLABLE
                        )
                    ),
                    'keys' => array(
                        'IDX_KEYNAME' => new Key('IDX_KEYNAME', array('column_two'), Key::TYPE_INDEX)
                    ),
                    'foreignKeys' => array(
                        'FK_TABLE1_NAME_ONE' => new ForeignKey(
                            'FK_TABLE1_NAME_ONE', 
                            array('column_one'), 
                            'table_name_two',
                            array('column_one'),
                            ForeignKey::ACTION_NO_ACTION,
                            ForeignKey::ACTION_NO_ACTION
                         ),
                        'FK_TABLE1_NAME_TWO' => new ForeignKey(
                            'FK_TABLE1_NAME_TWO',
                            array('column_two'),
                            'table_name_two',
                            array('column_two'),
                            ForeignKey::ACTION_SET_DEFAULT,
                            ForeignKey::ACTION_SET_DEFAULT
                        )
                    )
                    
                )
            ),
            array(
                'table_direct_columns_keys_foreign_keys',
                array(
                    'column_one' => new Column(
                        'column_one', Column::TYPE_TINYINT, '1', 3, null,
                        Column::OPTION_NULLABLE | Column::OPTION_UNSIGNED
                        | Column::OPTION_PRIMARY | Column::OPTION_IDENTITY
                    ),
                    'column_two' => new Column(
                        'column_two', Column::TYPE_INTEGER, null, 10
                    ),
                    'column_three' => new Column(
                        'column_three', Column::TYPE_DECIMAL, '0.0000', 12,
                        4, Column::OPTION_NULLABLE
                    )
                ),
                array(
                    'key_one' => new Key('IDX_KEYNAME', array('column_two'), Key::TYPE_INDEX)
                ),
                array(
                    'FK_TABLE1_NAME_ONE' => new ForeignKey(
                        'FK_TABLE1_NAME_ONE',
                        array('column_one'),
                        'table_name_two',
                        array('column_one'),
                        ForeignKey::ACTION_NO_ACTION,
                        ForeignKey::ACTION_NO_ACTION
                    ),
                    'FK_TABLE1_NAME_TWO' => new ForeignKey(
                        'FK_TABLE1_NAME_TWO',
                        array('column_two'),
                        'table_name_two',
                        array('column_two'),
                        ForeignKey::ACTION_SET_DEFAULT,
                        ForeignKey::ACTION_SET_DEFAULT
                    )
                ),
                array(
                    'name' => 'table_direct_columns_keys_foreign_keys',
                    'columns' => array(
                        'column_one' => new Column(
                            'column_one', Column::TYPE_TINYINT, '1', 3, null,
                            Column::OPTION_NULLABLE | Column::OPTION_UNSIGNED
                            | Column::OPTION_PRIMARY | Column::OPTION_IDENTITY
                        ),
                        'column_two' => new Column(
                            'column_two', Column::TYPE_INTEGER, null, 10
                        ),
                        'column_three' => new Column(
                            'column_three', Column::TYPE_DECIMAL, '0.0000', 12,
                            4, Column::OPTION_NULLABLE
                        )
                    ),
                    'keys' => array(
                        'IDX_KEYNAME' => new Key('IDX_KEYNAME', array('column_two'), Key::TYPE_INDEX)
                    ),
                    'foreignKeys' => array(
                        'FK_TABLE1_NAME_ONE' => new ForeignKey(
                            'FK_TABLE1_NAME_ONE',
                            array('column_one'),
                            'table_name_two',
                            array('column_one'),
                            ForeignKey::ACTION_NO_ACTION,
                            ForeignKey::ACTION_NO_ACTION
                        ),
                        'FK_TABLE1_NAME_TWO' => new ForeignKey(
                            'FK_TABLE1_NAME_TWO',
                            array('column_two'),
                            'table_name_two',
                            array('column_two'),
                            ForeignKey::ACTION_SET_DEFAULT,
                            ForeignKey::ACTION_SET_DEFAULT
                        )
                    )

                )
            )
        );
    }

    /**
     * @param string $name
     * @param array[] $columns
     * @param array[] $keys
     * @param array[] $foreignKeys
     * @param array $expectedData
     * @dataProvider dataProviderTableConstructor
     */
    public function testItCreatesCorrectlyTable($name, array $columns, array $keys, 
                                                array $foreignKeys, array $expectedData)
    {
        $table = new Table($name, $columns, $keys, $foreignKeys);
        if (isset($expectedData['name'])) {
            $this->assertEquals($expectedData['name'], $table->getName());
        }

        if (isset($expectedData['columns'])) {
            $this->assertEquals($expectedData['columns'], $table->getColumns());
        }

        if (isset($expectedData['keys'])) {
            $this->assertEquals($expectedData['keys'], $table->getKeys());
        }

        if (isset($expectedData['foreignKeys'])) {
            $this->assertEquals($expectedData['foreignKeys'], $table->getForeignKeys());
        }
    }
    
    public function testItWorksWithChildTables()
    {
        $this->assertClassHasAttribute('childTables', 'EcomDev_Fixture_Db_Schema_Table');
        
        $table = new Table('testTable');
        $this->assertEmpty($table->getChildTables());
        
        $childTables = array('test_table');
        $table->setChildTables($childTables);
        
        $this->assertSame($childTables, $table->getChildTables());
    }

    public function testItWorksWithParentTables()
    {
        $this->assertClassHasAttribute('childTables', 'EcomDev_Fixture_Db_Schema_Table');

        $table = new Table('testTable');
        $this->assertEmpty($table->getParentTables());

        $childTables = array('test_table');
        $table->setParentTables($childTables);

        $this->assertSame($childTables, $table->getParentTables());
    }

    /**
     * @param string $name
     * @param array[] $columns
     * @param array[] $keys
     * @param Key|false $expectedPrimaryKey
     * @dataProvider dataProviderTablePrimaryKey
     */
    public function testItReturnsPrimaryKey($name, array $columns, array $keys, $expectedPrimaryKey)
    {
        $table = new Table($name, $columns, $keys);
        
        $this->assertEquals($expectedPrimaryKey, $table->getPrimaryKey());
    }

    /**
     * @param string $name
     * @param Column[] $columns
     * @param Column|Column[]|false $expectedColumns
     * @dataProvider dataProviderTablePrimaryKeyColumns
     */
    public function testItReturnsPrimaryKeyColumns($name, $columns, $expectedColumns)
    {
        /** @var Table|PHPUnit_Framework_MockObject_MockObject $table */
        $table = $this->getMock('EcomDev_Fixture_Db_Schema_Table', array('getColumns'), array($name));
        $table->expects($this->once())
            ->method('getColumns')
            ->willReturn($columns);
        
        $this->assertSame($expectedColumns, $table->getPrimaryKeyColumn());
        // Second time it should not invoke getColumns
        $this->assertSame($expectedColumns, $table->getPrimaryKeyColumn());
    }

    /**
     * @return array
     */
    public function dataProviderTablePrimaryKeyColumns()
    {
        return array(
            'table_with_single_primary_key' => array( // Table has a single primary key
                'table_one',
                $columns = array(
                    'column_one' => new Column('column_one', Column::TYPE_SMALLINT, null, 3, null, Column::OPTION_PRIMARY),
                    'column_two' => new Column('column_two', Column::TYPE_INTEGER, null, 10, null),
                    'column_three' => new Column('column_three', Column::TYPE_DECIMAL, null, 12, 4, Column::OPTION_NULLABLE),
                ),
                $columns['column_one']
            ),
            'table_with_combined_primary_key' => array( // Table has a combined primary key
                'table_two',
                $columns = array(
                    'column_one' => new Column('column_one', Column::TYPE_SMALLINT, null, 3, null, Column::OPTION_PRIMARY),
                    'column_two' => new Column('column_two', Column::TYPE_INTEGER, null, 10, null, Column::OPTION_PRIMARY),
                    'column_three' => new Column('column_three', Column::TYPE_DECIMAL, null, 12, 4, Column::OPTION_NULLABLE),
                ),
                array(
                    'column_one' => $columns['column_one'],
                    'column_two' => $columns['column_two']
                )
            ),
            'table_with_no_primary_keys' => array( // Table has a combined primary key
                'table_two',
                $columns = array(
                    'column_one' => new Column('column_one', Column::TYPE_SMALLINT, null, 3, null),
                    'column_two' => new Column('column_two', Column::TYPE_INTEGER, null, 10, null),
                    'column_three' => new Column('column_three', Column::TYPE_DECIMAL, null, 12, 4, Column::OPTION_NULLABLE),
                ),
                false
            )
        );
    }

    public function dataProviderTablePrimaryKey()
    {
        return array(
            array( // Table has a primary key
                'table_one',
                array(
                    'column_one' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_one',
                        'COLUMN_NAME'      => 'column_one',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_TINYINT,
                        'DEFAULT'          => '1',
                        'NULLABLE'         => true,
                        'LENGTH'           => 3,
                        'SCALE'            => null,
                        'PRECISION'        => null,
                        'UNSIGNED'         => true,
                        'PRIMARY'          => true,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => true
                    ),
                    'column_two' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_one',
                        'COLUMN_NAME'      => 'column_two',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_INTEGER,
                        'DEFAULT'          => null,
                        'NULLABLE'         => false,
                        'LENGTH'           => 10,
                        'SCALE'            => null,
                        'PRECISION'        => null,
                        'UNSIGNED'         => false,
                        'PRIMARY'          => false,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => false
                    ),
                    'column_three' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_one',
                        'COLUMN_NAME'      => 'column_three',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_DECIMAL,
                        'DEFAULT'          => '0.0000',
                        'NULLABLE'         => true,
                        'LENGTH'           => null,
                        'SCALE'            => 4,
                        'PRECISION'        => 12,
                        'UNSIGNED'         => false,
                        'PRIMARY'          => false,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => false
                    )
                ),
                array(
                    strtoupper(Key::TYPE_PRIMARY) => array(
                        'SCHEMA_NAME'   => '',
                        'TABLE_NAME'    => 'table_one',
                        'KEY_NAME'      => strtoupper(Key::TYPE_PRIMARY),
                        'COLUMNS_LIST'  => array('column_one'),
                        'INDEX_TYPE'    => Key::TYPE_PRIMARY,
                        'INDEX_METHOD'  => 'BTREE',
                        'type'          => Key::TYPE_PRIMARY,
                        'fields'        => array('column_one')
                    )
                ),
                new Key(strtoupper(Key::TYPE_PRIMARY), array('column_one'), Key::TYPE_PRIMARY)
            ),
            array( // Table does not have a primary key
                'table_two',
                array(
                    'column_one' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_two',
                        'COLUMN_NAME'      => 'column_one',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_TINYINT,
                        'DEFAULT'          => '1',
                        'NULLABLE'         => true,
                        'LENGTH'           => 3,
                        'SCALE'            => null,
                        'PRECISION'        => null,
                        'UNSIGNED'         => true,
                        'PRIMARY'          => true,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => true
                    ),
                    'column_two' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_two',
                        'COLUMN_NAME'      => 'column_two',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_INTEGER,
                        'DEFAULT'          => null,
                        'NULLABLE'         => false,
                        'LENGTH'           => 10,
                        'SCALE'            => null,
                        'PRECISION'        => null,
                        'UNSIGNED'         => false,
                        'PRIMARY'          => false,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => false
                    ),
                    'column_three' => array(
                        'SCHEMA_NAME'      => null,
                        'TABLE_NAME'       => 'table_two',
                        'COLUMN_NAME'      => 'column_three',
                        'COLUMN_POSITION'  => 1,
                        'DATA_TYPE'        => Column::TYPE_DECIMAL,
                        'DEFAULT'          => '0.0000',
                        'NULLABLE'         => true,
                        'LENGTH'           => null,
                        'SCALE'            => 4,
                        'PRECISION'        => 12,
                        'UNSIGNED'         => false,
                        'PRIMARY'          => false,
                        'PRIMARY_POSITION' => 0,
                        'IDENTITY'         => false
                    )
                ),
                array(
                    'key_one' => array(
                        'SCHEMA_NAME'   => '',
                        'TABLE_NAME'    => 'table_two',
                        'KEY_NAME'      => 'IDX_KEYNAME',
                        'COLUMNS_LIST'  => array('column_two'),
                        'INDEX_TYPE'    => Key::TYPE_INDEX,
                        'INDEX_METHOD'  => 'BTREE',
                        'type'          => Key::TYPE_INDEX,
                        'fields'        => array('column_two')
                    )
                ),
                false
            )
        );
    }
}
