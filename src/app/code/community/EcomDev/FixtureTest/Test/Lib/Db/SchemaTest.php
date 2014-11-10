<?php

use EcomDev_Fixture_Db_Schema_Table as Table;
use EcomDev_Fixture_Db_Schema_Column as Column;
use EcomDev_Fixture_Db_Schema_Key as Key;
use EcomDev_Fixture_Db_Schema_ForeignKey as ForeignKey;
use EcomDev_Fixture_Db_Schema as Schema;
use EcomDev_Utils_Reflection as ReflectionUtil;

class EcomDev_FixtureTest_Test_Lib_Db_SchemaTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    const CLASS_TABLE = 'EcomDev_Fixture_Db_Schema_Table';
    const CLASS_DB_SCHEMA = 'EcomDev_Fixture_Db_Schema';
    const CLASS_DB_SCHEMA_INFORMATION_PROVIDER = 'EcomDev_Fixture_Contract_Db_Schema_InformationProviderInterface';
    
    /**
     * @var EcomDev_Fixture_Contract_Db_Schema_InformationProviderInterface
     */
    protected $informationProviderMock;
    
    protected function getInformationProviderMock()
    {
        if ($this->informationProviderMock === null) {
            $this->informationProviderMock = $this->getMockBuilder(self::CLASS_DB_SCHEMA_INFORMATION_PROVIDER)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->getMock();
        }

        return $this->informationProviderMock;
    }

    public function testItSetsDefaultParametersFromConstructor()
    {
        $info = new Schema($this->getInformationProviderMock());

        $this->assertSame($this->getInformationProviderMock(), $info->getInformationProvider());
        $this->assertEquals(self::CLASS_TABLE, $info->getTableInfoClass());
    }
    
    public function testItHasRequiredAttributes()
    {
        $this->assertTrue(class_exists(self::CLASS_DB_SCHEMA));
        $this->assertClassHasAttribute('informationProvider', self::CLASS_DB_SCHEMA);
        $this->assertClassHasAttribute('tableInfoClass', self::CLASS_DB_SCHEMA);
        $this->assertClassHasAttribute('tables', self::CLASS_DB_SCHEMA);
        $this->assertClassHasAttribute('tableRelations', self::CLASS_DB_SCHEMA);
        $this->assertClassHasAttribute('tableNamesSortedByRelation', self::CLASS_DB_SCHEMA);
    }

    /**
     * 
     */
    public function testItFetchesDataOnlyOnce()
    {
        $this->getInformationProviderMock()->expects($this->never())
            ->method('getTableNames');
        
        $this->getInformationProviderMock()->expects($this->never())
            ->method('getColumns');
        
        $this->getInformationProviderMock()->expects($this->never())
            ->method('getIndexes');

        $this->getInformationProviderMock()->expects($this->never())
            ->method('getForeignKeys');
        
        $info = new Schema($this->getInformationProviderMock());
        ReflectionUtil::setRestrictedPropertyValues($info, array(
            'tables' => array()
        ));
        $this->assertSame($info, $info->fetch());
    }

    /**
     * @param $tables
     * @dataProvider dataProviderTableData
     */
    public function testItFetchesCorrectTableData($tables)
    {
        $this->getInformationProviderMock()->expects($this->once())
            ->method('getTableNames')
            ->willReturn(array_keys($tables));

        $numberOfTables = count($tables);

        $this->getInformationProviderMock()->expects($this->exactly($numberOfTables))
            ->method('getColumns')
            ->willReturnCallback(function ($tableName) use ($tables) {
                return $tables[$tableName]['getColumns'];
            });

        $this->getInformationProviderMock()->expects($this->exactly($numberOfTables))
            ->method('getIndexes')
            ->willReturnCallback(function ($tableName) use ($tables) {
                return $tables[$tableName]['getIndexes'];
            });

        $this->getInformationProviderMock()->expects($this->exactly($numberOfTables))
            ->method('getForeignKeys')
            ->willReturnCallback(function ($tableName) use ($tables) {
                return $tables[$tableName]['getForeignKeys'];
            });

        $info = new Schema($this->getInformationProviderMock());
        $this->assertSame($info, $info->fetch());

        $dataSetName = ReflectionUtil::getRestrictedPropertyValue($this, 'dataName');
        $tables = $this->dataProviderTables();
        $tables = $tables[$dataSetName][0];
        $this->assertAttributeEquals($tables, 'tables', $info);
        $this->assertEquals(array_keys($tables), $info->getTableNames());
    }

    /**
     * @param $tables
     * @dataProvider dataProviderTableData
     */
    public function testItCorrectlySerializesTableData($tables)
    {
        $this->getInformationProviderMock()->expects($this->once())
            ->method('getTableNames')
            ->willReturn(array_keys($tables));
        
        $numberOfTables = count($tables);
        
        $this->getInformationProviderMock()->expects($this->exactly($numberOfTables))
            ->method('getColumns')
            ->willReturnCallback(function ($tableName) use ($tables) {
                return $tables[$tableName]['getColumns'];
            });

        $this->getInformationProviderMock()->expects($this->exactly($numberOfTables))
            ->method('getIndexes')
            ->willReturnCallback(function ($tableName) use ($tables) {
                return $tables[$tableName]['getIndexes'];
            });

        $this->getInformationProviderMock()->expects($this->exactly($numberOfTables))
            ->method('getForeignKeys')
            ->willReturnCallback(function ($tableName) use ($tables) {
                return $tables[$tableName]['getForeignKeys'];
            });

        $info = new Schema($this->getInformationProviderMock());
        
        $serializedInfo = serialize($info);
        $restoredInfo = unserialize($serializedInfo);
        
        $this->assertInstanceOf('EcomDev_Fixture_Db_Schema', $restoredInfo);
        
        $this->assertSame($info->getTableNames(), $restoredInfo->getTableNames());
        $this->assertSame($info->getTableNamesSortedByRelation(), $restoredInfo->getTableNamesSortedByRelation());
        $this->assertSame($info->getTableInfoClass(), $restoredInfo->getTableInfoClass());
        $this->assertAttributeEquals($this->readAttribute($info, 'tables'), 'tables', $restoredInfo);
        $this->assertAttributeEquals($this->readAttribute($info, 'tableRelations'), 'tableRelations', $restoredInfo);
    }

    /**
     * Tests that getTableInfo works correctly
     * 
     * @param Table[] $tables
     * @param string $tableName
     * @param Table $expectedTable
     * @dataProvider dataProviderTableInfo
     */
    public function testItFetchesSingleTableInfo($tables, $tableName, $expectedTable)
    {
        $info = new Schema($this->getInformationProviderMock());
        ReflectionUtil::setRestrictedPropertyValue($info, 'tables', $tables);
        $this->assertEquals($expectedTable, $info->getTableInfo($tableName));
    }

    /**
     * Tests that getTableInfo works correctly
     *
     * @param Table[] $tables
     * @param string $tableName
     * @param Table $expectedTable
     * @dataProvider dataProviderTableAncestors
     */
    public function testItFetchesSingleTableAncestors($tables, $tableName, $expectedTables)
    {
        $info = new Schema($this->getInformationProviderMock());
        ReflectionUtil::setRestrictedPropertyValue($info, 'tables', $tables);
        $this->assertEquals($expectedTables, $info->getTableAncestors($tableName));
    }

    /**
     * Tests that getTableInfo works correctly
     *
     * @param Table[] $tables
     * @param string $tableName
     * @param Table $expectedTable
     * @dataProvider dataProviderTableDescendants
     */
    public function testItFetchesSingleTableDescendants($tables, $tableName, $expectedTables)
    {
        $info = new Schema($this->getInformationProviderMock());
        ReflectionUtil::setRestrictedPropertyValue($info, 'tables', $tables);
        $this->assertEquals($expectedTables, $info->getTableDescendants($tableName));
    }
    
    /**
     * @expectedException EcomDev_Fixture_Db_Schema_Exception
     * @expectedExceptionMessage Requested table "wrong_table" does not exists.
     * @expectedExceptionCode 1
     */
    public function testItRisesExceptionIfTableIsNotFoundForInfoLookUp()
    {
        $this->getInformationProviderMock()->expects($this->once())
            ->method('getTableNames')
            ->willReturn(array());
        
        $info = new Schema($this->getInformationProviderMock());
        $info->getTableInfo('wrong_table');
    }

    /**
     * @expectedException EcomDev_Fixture_Db_Schema_Exception
     * @expectedExceptionMessage Requested table "wrong_table" does not exists.
     * @expectedExceptionCode 1
     */
    public function testItRisesExceptionIfTableIsNotFoundForAncestorsLookUp()
    {
        $this->getInformationProviderMock()->expects($this->once())
            ->method('getTableNames')
            ->willReturn(array());

        $info = new Schema($this->getInformationProviderMock());
        $info->getTableAncestors('wrong_table');
    }
    

    /**
     * @expectedException EcomDev_Fixture_Db_Schema_Exception
     * @expectedExceptionMessage Requested table "wrong_table" does not exists.
     * @expectedExceptionCode 1
     */
    public function testItRisesExceptionIfTableIsNotFoundForDescendantsLookUp()
    {
        $this->getInformationProviderMock()->expects($this->once())
            ->method('getTableNames')
            ->willReturn(array());

        $info = new Schema($this->getInformationProviderMock());
        $info->getTableDescendants('wrong_table');
    }

    /**
     * Tests if table relations are cached in memory and only once gets loaded
     */
    public function testItFetchesTableRelationsOnlyOnce()
    {
        $info = new Schema($this->getInformationProviderMock());

        $this->getInformationProviderMock()->expects($this->once())
            ->method('getTableNames')
            ->willReturn(array());

        $this->assertSame($info, $info->fetchTableRelations());
        $this->assertAttributeEquals(array(), 'tables', $info);
        $this->assertAttributeEquals(array(), 'tableRelations', $info);
        $this->assertSame($info, $info->fetchTableRelations());
    }

    /**
     * @param $tables
     * @dataProvider dataProviderTables
     */
    public function testItFetchesTableRelationsCorrectly($tables)
    {
        $info = new Schema($this->getInformationProviderMock());
        
        ReflectionUtil::setRestrictedPropertyValue($info, 'tables', $tables);
        $dataSetName = ReflectionUtil::getRestrictedPropertyValue($this, 'dataName');
        $expectedTables = $this->expectedTableRelations();
        $this->assertSame($info, $info->fetchTableRelations());
        $this->assertAttributeEquals($expectedTables[$dataSetName], 'tableRelations', $info);
    }



    /**
     * Tests that properties containing tables are resetable
     */
    public function testItIsPossibleToResetObject()
    {
        $info = new Schema($this->getInformationProviderMock());

        ReflectionUtil::setRestrictedPropertyValues($info, array(
            'tables' => array(),
            'tableRelations' => array(),
            'tableNamesSortedByRelation' => array()
        ));
        
        $this->getInformationProviderMock()->expects($this->once())
            ->method('reset')
            ->willReturnSelf();

        $this->assertSame($info, $info->reset());

        // These properties should be reset
        $this->assertAttributeSame(null, 'tables', $info);
        $this->assertAttributeSame(null, 'tableRelations', $info);
        $this->assertAttributeSame(null, 'tableNamesSortedByRelation', $info);

        // But table class name and adapter should stay the same
        $this->assertAttributeSame($this->getInformationProviderMock(), 'informationProvider', $info);
        $this->assertAttributeSame(self::CLASS_TABLE, 'tableInfoClass', $info);
    }

    /**
     * It does a sort process only onces
     */
    public function testItSortsTablesOnlyOnes()
    {
        $info = new Schema($this->getInformationProviderMock());
        
        ReflectionUtil::setRestrictedPropertyValue($info, 'tables', array());
        $this->assertSame(array(), $info->getTableNamesSortedByRelation());
        
        // Set value to make sure it uses cached value 
        ReflectionUtil::setRestrictedPropertyValue($info, 'tableNamesSortedByRelation', array('some_random_data'));
        $this->assertSame(array('some_random_data'), $info->getTableNamesSortedByRelation());
    }

    /**
     * Test it sorts tables
     * @dataProvider dataProviderTables
     */
    public function testItSortsTablesByRelations($tables)
    {
        $info = new Schema($this->getInformationProviderMock());
        ReflectionUtil::setRestrictedPropertyValue($info, 'tables', $tables);

        $dataSetName = ReflectionUtil::getRestrictedPropertyValue($this, 'dataName');
        $expectedOrder = $this->expectedTableOrder();
        
        $this->assertSame(
            $expectedOrder[$dataSetName], 
            $info->getTableNamesSortedByRelation()
        );
    }
    
    /**
     * Emulates getColumns column definition
     *
     * @param int $position
     * @param string $table
     * @param string $column
     * @param string $type
     * @param null|int $length
     * @param null|int $default
     * @param null|int $options
     * @param null|int $scale
     * @param null|int $precision
     * @return array
     */
    protected function getColumnDefinition(
        $position, $table, $column,
        $type, $length = null,
        $default = null, $options = null,
        $scale = null, $precision = null
    )
    {
        $options = (int) $options;
        return  array(
            'SCHEMA_NAME'      => null,
            'TABLE_NAME'       => $table,
            'COLUMN_NAME'      => $column,
            'COLUMN_POSITION'  => $position,
            'DATA_TYPE'        => $type,
            'DEFAULT'          => $default,
            'NULLABLE'         => ($options & Column::OPTION_NULLABLE) === Column::OPTION_NULLABLE,
            'LENGTH'           => $length,
            'SCALE'            => $scale,
            'PRECISION'        => $precision,
            'UNSIGNED'         => ($options & Column::OPTION_UNSIGNED) === Column::OPTION_UNSIGNED,
            'PRIMARY'          => ($options & Column::OPTION_PRIMARY) === Column::OPTION_PRIMARY,
            'PRIMARY_POSITION' => 0,
            'IDENTITY'         => ($options & Column::OPTION_IDENTITY) === Column::OPTION_IDENTITY
        );
    }

    /**
     * Return column definitions to emulate getColumns
     *
     * @param $columns
     * @param $table
     * @return array
     */
    protected function getColumnDefinitions($columns, $table)
    {
        $result = array();
        $position = 0;
        foreach ($columns as $column) {
            $column = array_pad($column, 7, null);

            list (
                $column, $type, $default, 
                $length, $options, $scale, $precision
                ) = $column;

            $result[$column] = $this->getColumnDefinition(
                $position++, $table, $column, $type,
                $length, $default, $options, $scale,
                $precision
            );
        }

        return $result;
    }

    /**
     * Return key definitions to emulate getIndexes
     *
     * @param $keys
     * @param $table
     * @return array
     */
    protected function getKeyDefinitions($keys, $table)
    {
        $result = array();
        foreach ($keys as $key) {
            list ($name, $columnList, $indexType) = $key;

            $result[strtoupper($name)] = $this->getKeyDefinition(
                $name, $table, $columnList, $indexType
            );
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $table
     * @param string[] $columnList
     * @param string $indexType
     * @return array
     */
    protected function getKeyDefinition($name, $table, $columnList, $indexType)
    {
        return array(
            'SCHEMA_NAME'   => '',
            'TABLE_NAME'    => $table,
            'KEY_NAME'      => $name,
            'COLUMNS_LIST'  => $columnList,
            'INDEX_TYPE'    => $indexType,
            'INDEX_METHOD'  => 'BTREE',
            'type'          => $indexType,
            'fields'        => $columnList
        );
    }

    /**
     * Return key definitions to emulate getForeignKeys
     *
     * @param $keys
     * @param $table
     * @return array
     */
    protected function getForeignKeyDefinitions($keys, $table)
    {
        $result = array();
        foreach ($keys as $key) {
            list ($name, $column, $refTable, $refColumn, $onDelete, $onUpdate) = $key;

            $result[strtoupper($name)] = $this->getForeignKeyDefinition(
                $name, $table, $column, $refTable, $refColumn, $onDelete, $onUpdate
            );
        }

        return $result;
    }

    /**
     * Creates a foreign key definition array, similar to a result of getForeignKey
     *
     * @param $name
     * @param $table
     * @param $column
     * @param $refTable
     * @param $refColumn
     * @param $onDelete
     * @param $onUpdate
     * @return array
     */
    protected function getForeignKeyDefinition($name, $table, $column, $refTable, $refColumn, $onDelete, $onUpdate)
    {
        return array(
            'FK_NAME'           => $name,
            'SCHEMA_NAME'       => '',
            'TABLE_NAME'        => $table,
            'COLUMN_NAME'       => $column,
            'REF_SHEMA_NAME'    => '',
            'REF_TABLE_NAME'    => $refTable,
            'REF_COLUMN_NAME'   => $refColumn,
            'ON_DELETE'         => $onDelete,
            'ON_UPDATE'         => $onUpdate
        );
    }

    /**
     * Return emulated table calls
     *
     * @param string $table
     * @param array $columns
     * @param array $keys
     * @param array $foreignKeys
     * @return array[]
     */
    protected function getEmulatedTableCallResult($table, array $columns, array $keys, array $foreignKeys)
    {
        return array(
            'getColumns' => $this->getColumnDefinitions($columns, $table),
            'getForeignKeys' => $this->getForeignKeyDefinitions($foreignKeys, $table),
            'getIndexes' => $this->getKeyDefinitions($keys, $table)
        );
    }

    /**
     * Provides data for fetch method of the extension
     * 
     * @return array
     */
    public function dataProviderTableData()
    {
        return array(
            // table e, table f, table d, table a, table c, table d
            'first_relation' => array(
                array(
                    'table_a' => $this->getEmulatedTableCallResult(
                        'table_a',
                        array(
                            array('column_a', Column::TYPE_INTEGER, '1', 10,
                                  Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED),
                            array('column_d', Column::TYPE_INTEGER, null, 10, Column::OPTION_UNSIGNED),
                            array('column_text', Column::TYPE_TEXT, null, 10, Column::OPTION_NULLABLE),
                        ),
                        array(
                            array(
                                'PRIMARY', array('column_a'), Key::TYPE_PRIMARY
                            ),
                            array(
                                'idx_column_d', array('column_d'), Key::TYPE_INDEX
                            )
                        ),
                        array(
                            array(
                                'FK_table_a_column_d', 'column_d',
                                'table_d', 'column_d', ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE
                            )
                        )
                    ),  // Depends on d
                    'table_b' => $this->getEmulatedTableCallResult(
                        'table_b',
                        array(
                            array('column_b', Column::TYPE_INTEGER, '1', 10,
                                  Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED),
                            array('column_c', Column::TYPE_INTEGER, null, 10, Column::OPTION_UNSIGNED),
                            array('column_text', Column::TYPE_TEXT, null, 10, Column::OPTION_NULLABLE),
                        ),
                        array(
                            array(
                                'PRIMARY', array('column_b'), Key::TYPE_PRIMARY
                            ),
                            array(
                                'idx_column_c', array('column_c'), Key::TYPE_INDEX
                            )
                        ),
                        array(
                            array(
                                'FK_table_b_column_c', 'column_c',
                                'table_c', 'column_c', ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE
                            )
                        )
                    ),  // Depends on c
                    'table_e' => $this->getEmulatedTableCallResult(
                        'table_e',
                        array(
                            array('column_e', Column::TYPE_INTEGER, '1', 10,
                                  Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED),
                            array('column_text', Column::TYPE_TEXT, null, 10, Column::OPTION_NULLABLE),
                        ),
                        array(
                            array(
                                'PRIMARY', array('column_e'), Key::TYPE_PRIMARY
                            )
                        ),
                        array()
                    ),
                    'table_c' => $this->getEmulatedTableCallResult(
                        'table_c',
                        array(
                            array('column_c', Column::TYPE_INTEGER, '1', 10,
                                  Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED),
                            array('column_a', Column::TYPE_INTEGER, null, 10, Column::OPTION_UNSIGNED),
                            array('column_text', Column::TYPE_TEXT, null, 10, Column::OPTION_NULLABLE),
                        ),
                        array(
                            array(
                                'PRIMARY', array('column_c'), Key::TYPE_PRIMARY
                            ),
                            array(
                                'idx_column_a', array('column_a'), Key::TYPE_INDEX
                            )
                        ),
                        array(
                            array(
                                'FK_table_c_column_a', 'column_a',
                                'table_a', 'column_a', ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE
                            )
                        )
                    ), // Depends on a
                    'table_d' => $this->getEmulatedTableCallResult(
                        'table_d',
                        array(
                            array('column_d', Column::TYPE_INTEGER, '1', 10,
                                  Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED),
                            array('column_f', Column::TYPE_INTEGER, null, 10, Column::OPTION_UNSIGNED),
                            array('column_text', Column::TYPE_TEXT, null, 10, Column::OPTION_NULLABLE),
                        ),
                        array(
                            array(
                                'PRIMARY', array('column_d'), Key::TYPE_PRIMARY
                            ),
                            array(
                                'idx_column_f', array('column_f'), Key::TYPE_INDEX
                            )
                        ),
                        array(
                            array(
                                'FK_table_d_column_f', 'column_f',
                                'table_f', 'column_f', ForeignKey::ACTION_CASCADE, ForeignKey::ACTION_CASCADE
                            )
                        )
                    ), // Depends on f
                    'table_f' => $this->getEmulatedTableCallResult(
                        'table_f',
                        array(
                            array('column_f', Column::TYPE_INTEGER, '1', 10,
                                  Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED),
                            array('column_text', Column::TYPE_TEXT, null, 10, Column::OPTION_NULLABLE),
                        ),
                        array(
                            array(
                                'PRIMARY', array('column_f'), Key::TYPE_PRIMARY
                            )
                        ),
                        array()
                    )
                )
            )
        );
    }
    
    public function dataProviderTables()
    {
        return array(
            'first_relation' => array(
                array(
                    // table e, table f, table d, table a, table c, table d
                    'table_a' => ReflectionUtil::createObject(
                        self::CLASS_TABLE,
                        array(
                            'name' => 'table_a',
                            'columns' => array(
                                'column_a' => new Column(
                                    'column_a', Column::TYPE_INTEGER, '1', 10, null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED
                                ),
                                'column_d' => new Column(
                                    'column_d', Column::TYPE_INTEGER, null, 10, null, Column::OPTION_UNSIGNED
                                ),
                                'column_text' => new Column(
                                    'column_text', Column::TYPE_TEXT, null, 10, null, Column::OPTION_NULLABLE
                                ),
                            ),
                            'keys' => array(
                                'PRIMARY' => new Key(
                                    'PRIMARY', array('column_a'), Key::TYPE_PRIMARY
                                ),
                                'idx_column_d' => new Key(
                                    'idx_column_d', array('column_d'), Key::TYPE_INDEX
                                )
                            ),
                            'foreignKeys' => array(
                                'FK_table_a_column_d' => new ForeignKey(
                                    'FK_table_a_column_d', array('column_d'),
                                    'table_d', array('column_d'), 
                                    ForeignKey::ACTION_CASCADE, 
                                    ForeignKey::ACTION_CASCADE
                                )
                            ),
                            'parentTables' => array(
                                'table_d'
                            ),
                            'childTables' => array(
                                'table_c'
                            )
                        )
                    ),
                    'table_b' => ReflectionUtil::createObject(
                        self::CLASS_TABLE,
                        array(
                            'name' => 'table_b',
                            'columns' => array(
                                'column_b' => new Column(
                                    'column_b', Column::TYPE_INTEGER, '1', 10, null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED
                                ),
                                'column_c' => new Column(
                                    'column_c', Column::TYPE_INTEGER, null, 10, null, Column::OPTION_UNSIGNED
                                ),
                                'column_text' => new Column(
                                    'column_text', Column::TYPE_TEXT, null, 10, null, Column::OPTION_NULLABLE
                                ),
                            ),
                            'keys' => array(
                                'PRIMARY' => new Key(
                                    'PRIMARY', array('column_b'), Key::TYPE_PRIMARY
                                ),
                                'idx_column_c' => new Key(
                                    'idx_column_c', array('column_c'), Key::TYPE_INDEX
                                )
                            ),
                            'foreignKeys' => array(
                                'FK_table_b_column_c' => new ForeignKey(
                                    'FK_table_b_column_c', array('column_c'),
                                    'table_c', array('column_c'),
                                    ForeignKey::ACTION_CASCADE,
                                    ForeignKey::ACTION_CASCADE
                                )
                            ),
                            'parentTables' => array(
                                'table_c'
                            ),
                            'childTables' => array(
                            )
                        )
                    ),
                    'table_c' => ReflectionUtil::createObject(
                        self::CLASS_TABLE,
                        array(
                            'name' => 'table_c',
                            'columns' => array(
                                'column_c' => new Column(
                                    'column_c', Column::TYPE_INTEGER, '1', 10, null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED
                                ),
                                'column_a' => new Column(
                                    'column_a', Column::TYPE_INTEGER, null, 10, null, Column::OPTION_UNSIGNED
                                ),
                                'column_text' => new Column(
                                    'column_text', Column::TYPE_TEXT, null, 10, null, Column::OPTION_NULLABLE
                                ),
                            ),
                            'keys' => array(
                                'PRIMARY' => new Key(
                                    'PRIMARY', array('column_c'), Key::TYPE_PRIMARY
                                ),
                                'idx_column_a' => new Key(
                                    'idx_column_a', array('column_a'), Key::TYPE_INDEX
                                )
                            ),
                            'foreignKeys' => array(
                                'FK_table_c_column_a' => new ForeignKey(
                                    'FK_table_c_column_a', array('column_a'),
                                    'table_a', array('column_a'),
                                    ForeignKey::ACTION_CASCADE,
                                    ForeignKey::ACTION_CASCADE
                                )
                            ),
                            'parentTables' => array(
                                'table_a'
                            ),
                            'childTables' => array(
                                'table_b'
                            )
                        )
                    ),
                    'table_d' => ReflectionUtil::createObject(
                        self::CLASS_TABLE,
                        array(
                            'name' => 'table_d',
                            'columns' => array(
                                'column_d' => new Column(
                                    'column_d', Column::TYPE_INTEGER, '1', 10, null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED
                                ),
                                'column_f' => new Column(
                                    'column_f', Column::TYPE_INTEGER, null, 10, null, Column::OPTION_UNSIGNED
                                ),
                                'column_text' => new Column(
                                    'column_text', Column::TYPE_TEXT, null, 10, null, Column::OPTION_NULLABLE
                                ),
                            ),
                            'keys' => array(
                                'PRIMARY' => new Key(
                                    'PRIMARY', array('column_d'), Key::TYPE_PRIMARY
                                ),
                                'idx_column_f' => new Key(
                                    'idx_column_f', array('column_f'), Key::TYPE_INDEX
                                )
                            ),
                            'foreignKeys' => array(
                                'FK_table_d_column_f' => new ForeignKey(
                                    'FK_table_d_column_f', array('column_f'),
                                    'table_f', array('column_f'),
                                    ForeignKey::ACTION_CASCADE,
                                    ForeignKey::ACTION_CASCADE
                                )
                            ),
                            'parentTables' => array(
                                'table_f'
                            ),
                            'childTables' => array(
                                'table_a'
                            )
                        )
                    ),
                    'table_e' => ReflectionUtil::createObject(
                        self::CLASS_TABLE,
                        array(
                            'name' => 'table_e',
                            'columns' => array(
                                'column_e' => new Column(
                                    'column_e', Column::TYPE_INTEGER, '1', 10, null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED
                                ),
                                'column_text' => new Column(
                                    'column_text', Column::TYPE_TEXT, null, 10, null, Column::OPTION_NULLABLE
                                ),
                            ),
                            'keys' => array(
                                'PRIMARY' => new Key(
                                    'PRIMARY', array('column_e'), Key::TYPE_PRIMARY
                                )
                            ),
                            'foreignKeys' => array(
                            ),
                            'parentTables' => array(
                            ),
                            'childTables' => array(
                            )
                        )
                    ),
                    'table_f' => ReflectionUtil::createObject(
                        self::CLASS_TABLE,
                        array(
                            'name' => 'table_f',
                            'columns' => array(
                                'column_f' => new Column(
                                    'column_f', Column::TYPE_INTEGER, '1', 10, null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY | Column::OPTION_UNSIGNED
                                ),
                                'column_text' => new Column(
                                    'column_text', Column::TYPE_TEXT, null, 10, null, Column::OPTION_NULLABLE
                                ),
                            ),
                            'keys' => array(
                                'PRIMARY' => new Key(
                                    'PRIMARY', array('column_f'), Key::TYPE_PRIMARY
                                )
                            ),
                            'foreignKeys' => array(
                            ),
                            'parentTables' => array(
                            ),
                            'childTables' => array(
                                'table_d'
                            )
                        )
                    )
                )
            )
        );
    }

    protected function expectedTableRelations()
    {
        return array(
            'first_relation' => array(
                'table_a' => array(
                    'descendants' => array('table_c', 'table_b'),
                    'ancestors' => array('table_d', 'table_f'),
                ),
                'table_b' => array(
                    'descendants' => array(),
                    'ancestors' => array('table_c', 'table_a', 'table_d', 'table_f'),
                ),
                'table_c' => array(
                    'descendants' => array('table_b'),
                    'ancestors' => array('table_a', 'table_d', 'table_f'),
                ),
                'table_d' => array(
                    'descendants' => array('table_a', 'table_c', 'table_b'),
                    'ancestors' => array('table_f'),
                ),
                'table_e' => array(
                    'descendants' => array(),
                    'ancestors' => array(),
                ),
                'table_f' => array(
                    'descendants' => array('table_d', 'table_a', 'table_c', 'table_b'),
                    'ancestors' => array(),
                ),
            )
        );
    }
    
    protected function expectedTableOrder()
    {
        return array(
            'first_relation' => array(
                'table_e', 'table_f', 'table_d', 'table_a', 'table_c', 'table_b'
            )
        );
    }
    
    public function dataProviderTableInfo()
    {
        $tablesCalls = $this->dataProviderTables();
        
        $dataSet = array();
        foreach ($tablesCalls as $callName => $tables) {
            foreach ($tables[0] as $tableName => $table) {
                $dataSet[$callName . '_' . $tableName] = array(
                    $tables[0], $tableName, $table
                );
            }
        }
        
        return $dataSet;
    }
    
    public function dataProviderTableAncestors()
    {
        $tablesCalls = $this->dataProviderTables();
        $relationSets = $this->expectedTableRelations();

        $dataSet = array();
        foreach ($tablesCalls as $callName => $tables) {
            $relations = $relationSets[$callName];
            foreach ($tables[0] as $tableName => $table) {
                $dataSet[$callName . '_' . $tableName] = array($tables[0], $tableName, array());
                foreach ($relations[$tableName]['ancestors'] as $parentName) {
                    $dataSet[$callName . '_' . $tableName][2][$parentName] = $tables[0][$parentName];
                }
            }
        }

        return $dataSet;
    }

    public function dataProviderTableDescendants()
    {
        $tablesCalls = $this->dataProviderTables();
        $relationSets = $this->expectedTableRelations();

        $dataSet = array();
        foreach ($tablesCalls as $callName => $tables) {
            $relations = $relationSets[$callName];
            foreach ($tables[0] as $tableName => $table) {
                $dataSet[$callName . '_' . $tableName] = array($tables[0], $tableName, array());
                foreach ($relations[$tableName]['descendants'] as $childName) {
                    $dataSet[$callName . '_' . $tableName][2][$childName] = $tables[0][$childName];
                }
            }
        }

        return $dataSet;
    }
}
