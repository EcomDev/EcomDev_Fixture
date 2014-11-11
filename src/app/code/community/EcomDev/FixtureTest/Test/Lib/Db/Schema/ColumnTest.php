<?php

use EcomDev_Fixture_Db_Schema_Column as Column;

class EcomDev_FixtureTest_Test_Lib_Db_Schema_ColumnTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    public function testItHasRequiredAttributes()
    {
        $this->assertTrue(class_exists('EcomDev_Fixture_Db_Schema_Column'));
        $this->assertClassHasAttribute('name', 'EcomDev_Fixture_Db_Schema_Column');
        $this->assertClassHasAttribute('type', 'EcomDev_Fixture_Db_Schema_Column');
        $this->assertClassHasAttribute('length', 'EcomDev_Fixture_Db_Schema_Column');
        $this->assertClassHasAttribute('scale', 'EcomDev_Fixture_Db_Schema_Column');
        $this->assertClassHasAttribute('defaultValue', 'EcomDev_Fixture_Db_Schema_Column');
        $this->assertClassHasAttribute('options', 'EcomDev_Fixture_Db_Schema_Column');
    }
    
    public function dataProviderConstructorArguments()
    {
        return array(
            'int' => array('someName', Column::TYPE_INTEGER, '0', '10', null, Column::OPTION_NULLABLE),
            'int_2' => array('someName', Column::TYPE_INTEGER, '0', '10', null, Column::OPTION_NULLABLE 
                                                                                | Column::OPTION_UNSIGNED),
            'decimal' => array('someName', Column::TYPE_DECIMAL, '0.0000', '12', '4', Column::OPTION_UNSIGNED 
                                                                                      | Column::OPTION_PRIMARY),
            'primary' => array('someName', Column::TYPE_INTEGER, '0', '10', null, Column::OPTION_PRIMARY),
            'primary_identity' => array('someName', Column::TYPE_INTEGER, '0', '10', null, Column::OPTION_PRIMARY | Column::OPTION_IDENTITY)
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectName($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $this->assertEquals($name, $column->getName());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectType($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $this->assertEquals($type, $column->getType());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectDefaultValue($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $this->assertEquals($default, $column->getDefaultValue());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectOptions($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $this->assertEquals($options, $column->getOptions());
    }
    
    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectIsNullable($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $isNullable = ($options & Column::OPTION_NULLABLE) === Column::OPTION_NULLABLE;
        $this->assertEquals($isNullable, $column->isNullable());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectIsUnsigned($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $isUnsigned = ($options & Column::OPTION_UNSIGNED) === Column::OPTION_UNSIGNED;
        $this->assertEquals($isUnsigned, $column->isUnsigned());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectIsPrimary($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $isPrimary = ($options & Column::OPTION_PRIMARY) === Column::OPTION_PRIMARY;
        $this->assertEquals($isPrimary, $column->isPrimary());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectIsIdentity($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $isPrimary = ($options & Column::OPTION_IDENTITY) === Column::OPTION_IDENTITY;
        $this->assertEquals($isPrimary, $column->isIdentity());
    }


    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectLength($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $this->assertEquals($length, $column->getLength());
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $options
     * @param string $default
     * @param int|null $length
     * @param int|null $scale
     * @dataProvider dataProviderConstructorArguments
     */
    public function testItReturnsCorrectScale($name, $type, $default, $length, $scale, $options)
    {
        $column = new Column($name, $type, $default, $length, $scale, $options);
        $this->assertEquals($scale, $column->getScale());
    }

    /**
     * @return array
     */
    public function dataProviderIsInteger()
    {
        return array(
            'tinyint' => array(Column::TYPE_TINYINT, true),
            'smallint' => array(Column::TYPE_SMALLINT, true),
            'biglint' => array(Column::TYPE_BIGINT, true),
            'integer' => array(Column::TYPE_INTEGER, true),
            'decimal' => array(Column::TYPE_DECIMAL, false)
        );
    }

    /**
     * @param string $type
     * @param boolean $expectedValue
     * @dataProvider dataProviderIsInteger
     */
    public function testItCorrectlyDetectsIntegerColumn($type, $expectedValue)
    {
        $column = new Column('column_name', $type);
        $this->assertEquals($expectedValue, $column->isInteger());
    }

    /**
     * @return array
     */
    public function dataProviderIsDecimal()
    {
        return array(
            'decimal' => array(Column::TYPE_DECIMAL, true),
            'float' => array(Column::TYPE_FLOAT, true),
            'double' => array(Column::TYPE_DOUBLE, true),
            'real' => array(Column::TYPE_REAL, true),
            'numeric' => array(Column::TYPE_NUMERIC, true),
            'integer' => array(Column::TYPE_INTEGER, false)
        );
    }

    /**
     * @param string $type
     * @param boolean $expectedValue
     * @dataProvider dataProviderIsDecimal
     */
    public function testItCorrectlyDetectsDecimalColumn($type, $expectedValue)
    {
        $column = new Column('column_name', $type);
        $this->assertEquals($expectedValue, $column->isDecimal());
    }

    /**
     * @return array
     */
    public function dataProviderIsDateType()
    {
        return array(
            'date' => array(Column::TYPE_DATE, true),
            'datetime' => array(Column::TYPE_DATETIME, true),
            'time' => array(Column::TYPE_TIME, true),
            'timestamp' => array(Column::TYPE_TIMESTAMP, true),
            'decimal' => array(Column::TYPE_DECIMAL, false),
            'integer' => array(Column::TYPE_INTEGER, false)
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @param boolean $expectedValue
     * @dataProvider dataProviderIsDateType
     */
    public function testItCorrectlyDetectsDateTypeColumn($type, $expectedValue)
    {
        $column = new Column('column_name', $type);
        $this->assertEquals($expectedValue, $column->isDateBased());
    }

    /**
     * @return array
     */
    public function dataProviderIsString()
    {
        return array(
            'datetime' => array(Column::TYPE_DATETIME, false),
            'decimal' => array(Column::TYPE_DECIMAL, false),
            'integer' => array(Column::TYPE_INTEGER, false),
            'varchar' => array(Column::TYPE_VARCHAR, true),
            'char' => array(Column::TYPE_CHAR, true),
            'text' => array(Column::TYPE_TEXT, true),
            'tinytext' => array(Column::TYPE_TINYTEXT, true),
            'mediumtext' => array(Column::TYPE_MEDIUMTEXT, true),
            'longtext' => array(Column::TYPE_LONGTEXT, true),
            'blob' => array(Column::TYPE_BLOB, false),
        );
    }

    /**
     * @param string $type
     * @param boolean $expectedValue
     * @dataProvider dataProviderIsString
     */
    public function testItCorrectlyDetectsStringColumn($type, $expectedValue)
    {
        $column = new Column('column_name', $type);
        $this->assertEquals($expectedValue, $column->isString());
    }

    /**
     * @param string $type
     * @param mixed $actualValue
     * @param string|null $expectedValue
     * @param int $options
     * @param null|mixed $default
     * @param null|int $length
     * @param null|int $scale
     * @dataProvider dataProviderRecommendedValue
     */
    public function testItRecommendsCorrectValueForDatabase(
        $type, $actualValue, $expectedValue, $options = 0, $default = null, $length = null, $scale = null
    )
    {
        $column = new Column('column_name', $type, $default, $length, $scale, $options);
        
        $this->assertSame(
            $expectedValue,
            $column->getRecommendedValue($actualValue)
        );
    }
    
    public function dataProviderRecommendedValue()
    {
        return array(
            'integer_as_decimal' => array(
                Column::TYPE_INTEGER, 10.01, '10'
            ),
            'integer_nullable' => array(
                Column::TYPE_INTEGER, null, null, Column::OPTION_NULLABLE
            ),
            'integer_nullable_empty_string' => array(
                Column::TYPE_INTEGER, '', null, Column::OPTION_NULLABLE
            ),
            'integer_as_string_decimal' => array(
                Column::TYPE_INTEGER, '10.01', '10'
            ),
            'integer_not_nullable_null_default_value' => array(
                Column::TYPE_INTEGER, null, '1', 0, '1'
            ),
            'integer_not_nullable_null' => array(
                Column::TYPE_INTEGER, null, '0', 0
            ),
            'integer_not_nullable_empty_string' => array(
                Column::TYPE_INTEGER, '', '0', 0
            ),
            'decimal_as_integer' => array(
                Column::TYPE_DECIMAL, 10, '10.00', 0, null, 10, 2
            ),
            'decimal_as_float_4_scale' => array(
                Column::TYPE_DECIMAL, 10.01, '10.0100', 0, null, 10, 4
            ),
            'decimal_not_nullable_null_default_value' => array(
                Column::TYPE_DECIMAL, null, '9.99', 0, '9.99', 10, 4 // Default no modification if string
            ),
            'decimal_not_nullable_null' => array(
                Column::TYPE_DECIMAL, null, '0.0000', 0, null,10, 4
            ),
            'decimal_not_nullable_empty_string' => array(
                Column::TYPE_DECIMAL, '', '0.0000', 0, null, 10, 4
            ),
            'decimal_nullable_empty_string' => array(
                Column::TYPE_DECIMAL, '', null, Column::OPTION_NULLABLE, null, 10, 4
            ),            
            'datetype_as_string' => array( // Should not modify date if it is passed as string
                Column::TYPE_DATE, '2013-01-01 Something', '2013-01-01 Something'
            ),
            'datetype_as_null_nullable' => array(
                Column::TYPE_DATE, null, null, Column::OPTION_NULLABLE
            ),
            'datetype_as_empty_string_nullable' => array(
                Column::TYPE_DATE, '', null, Column::OPTION_NULLABLE
            ),
            'date_as_null' => array(
                Column::TYPE_DATE, null, '0000-00-00'
            ),
            'datetime_as_null' => array( 
                Column::TYPE_DATETIME, null, '0000-00-00 00:00:00'
            ),
            'date_as_empty_string' => array(
                Column::TYPE_DATE, '', '0000-00-00'
            ),
            'datetime_as_empty_string' => array(
                Column::TYPE_DATETIME, '', '0000-00-00 00:00:00'
            ),
            'timestamp_as_null' => array(
                Column::TYPE_TIMESTAMP, null, '0000-00-00 00:00:00'
            ),
            'timestamp_as_empty_string' => array(
                Column::TYPE_TIMESTAMP, '', '0000-00-00 00:00:00'
            ),
            'time_as_null' => array(
                Column::TYPE_TIME, null, '00:00:00'
            ),
            'time_as_empty_string' => array(
                Column::TYPE_TIME, '', '00:00:00'
            ),
            'date_as_date_object' => array(
                Column::TYPE_DATE, new DateTime('2013-01-01'), '2013-01-01'
            ),
            'datetime_as_date_object' => array(
                Column::TYPE_DATETIME, new DateTime('2013-01-01 03:01:02'), '2013-01-01 03:01:02'
            ),
            'timestamp_as_date_object' => array(
                Column::TYPE_TIMESTAMP, new DateTime('2013-01-01 03:01:02'), '2013-01-01 03:01:02'
            ),
            'time_as_date_object' => array(
                Column::TYPE_TIME, new DateTime('2013-01-01 03:01:02'), '03:01:02'
            ),
            'string_as_null' => array(
                Column::TYPE_VARCHAR, null, ''
            ),
            'string_as_null_default_value' => array(
                Column::TYPE_VARCHAR, null, 'default', 0, 'default'
            ),
            'string_as_empty_string_default_value' => array(
                Column::TYPE_VARCHAR, '', '', 0, 'default'
            ),
            'string_as_null_nullable' => array(
                Column::TYPE_VARCHAR, null, null, Column::OPTION_NULLABLE
            ),
            'string_too_long' => array(
                Column::TYPE_VARCHAR, 'too_long', 'too_', 0, null, 4 
            ),
            'string_too_long_unicode' => array(
                Column::TYPE_VARCHAR, 'много букв', 'много бу', 0, null, 8
            )
        );
    }
}
