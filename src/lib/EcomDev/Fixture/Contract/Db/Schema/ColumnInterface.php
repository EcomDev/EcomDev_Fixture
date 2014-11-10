<?php

/**
 * Interface of the column 
 */
interface EcomDev_Fixture_Contract_Db_Schema_ColumnInterface
{
    const TYPE_BOOLEAN          = Varien_Db_Ddl_Table::TYPE_BOOLEAN;
    const TYPE_SMALLINT         = Varien_Db_Ddl_Table::TYPE_SMALLINT;
    const TYPE_INTEGER          = Varien_Db_Ddl_Table::TYPE_INTEGER;
    const TYPE_BIGINT           = Varien_Db_Ddl_Table::TYPE_BIGINT;
    const TYPE_FLOAT            = Varien_Db_Ddl_Table::TYPE_FLOAT;
    const TYPE_NUMERIC          = Varien_Db_Ddl_Table::TYPE_NUMERIC;
    const TYPE_DECIMAL          = Varien_Db_Ddl_Table::TYPE_DECIMAL;
    const TYPE_DATE             = Varien_Db_Ddl_Table::TYPE_DATE;
    const TYPE_TIMESTAMP        = Varien_Db_Ddl_Table::TYPE_TIMESTAMP;
    const TYPE_DATETIME         = Varien_Db_Ddl_Table::TYPE_DATETIME;
    const TYPE_TEXT             = Varien_Db_Ddl_Table::TYPE_TEXT;
    const TYPE_TINYTEXT         = 'tinytext';
    const TYPE_MEDIUMTEXT       = 'mediumtext';
    const TYPE_LONGTEXT         = 'longtext';
    const TYPE_BLOB             = Varien_Db_Ddl_Table::TYPE_BLOB;
    const TYPE_TINYBLOB         = 'tinyblob';
    const TYPE_MEDIUMBLOB       = 'mediumblob';
    const TYPE_LONGBLOB         = 'longblob';
    const TYPE_VARBINARY        = Varien_Db_Ddl_Table::TYPE_VARBINARY;
    const TYPE_TINYINT          = Varien_Db_Ddl_Table::TYPE_TINYINT; 
    const TYPE_CHAR             = Varien_Db_Ddl_Table::TYPE_CHAR;
    const TYPE_VARCHAR          = Varien_Db_Ddl_Table::TYPE_VARCHAR;
    const TYPE_LONGVARCHAR      = Varien_Db_Ddl_Table::TYPE_LONGVARCHAR;
    const TYPE_CLOB             = Varien_Db_Ddl_Table::TYPE_CLOB;
    const TYPE_DOUBLE           = Varien_Db_Ddl_Table::TYPE_DOUBLE;
    const TYPE_REAL             = Varien_Db_Ddl_Table::TYPE_REAL;
    const TYPE_TIME             = Varien_Db_Ddl_Table::TYPE_TIME;
    const TYPE_BINARY           = Varien_Db_Ddl_Table::TYPE_BINARY;
    const TYPE_LONGVARBINARY    = Varien_Db_Ddl_Table::TYPE_LONGVARBINARY;
    
    const OPTION_NULLABLE = 1;
    const OPTION_UNSIGNED = 2;
    const OPTION_PRIMARY = 4;
    const OPTION_IDENTITY = 8;

    /**
     * @param string $name
     * @param string $type
     * @param string $defaultValue
     * @param int|null $length
     * @param int|null $scale
     * @param int $options
     */
    public function __construct($name, $type,  $defaultValue = null, $length = null, $scale = null, $options = 0);

    /**
     * Returns name of the column
     * 
     * @return string
     */
    public function getName();

    /**
     * Returns type of the column
     * 
     * @return string
     */
    public function getType();

    /**
     * Returns length of the column
     * 
     * @return int|null
     */
    public function getLength();

    /**
     * Returns scale of the column if applicable
     * 
     * @return int|null
     */
    public function getScale();

    /**
     * Returns default value of the column
     * 
     * @return string
     */
    public function getDefaultValue();

    /**
     * Returns a recommended value for a db column
     * 
     * @param string $value
     * @return string|null
     */
    public function getRecommendedValue($value);

    /**
     * Returns options flag
     *
     * @return int
     */
    public function getOptions();

    /**
     * Returns flag for nullable option
     * 
     * @return boolean
     */
    public function isNullable();

    /**
     * Returns flag for unsigned option
     *
     * @return boolean
     */
    public function isUnsigned();

    /**
     * Returns flag for primary option
     *
     * @return boolean
     */
    public function isPrimary();

    /**
     * Returns flag for identity option
     *
     * @return boolean
     */
    public function isIdentity();

    /**
     * Returns flag for integer type
     *
     * @return boolean
     */
    public function isInteger();

    /**
     * Returns flag for decimal type
     *
     * @return boolean
     */
    public function isDecimal();

    /**
     * Returns flag for date based type
     * 
     * @return boolean
     */
    public function isDateBased();

    /**
     * Returns flag for data that is a string value
     * 
     * @return boolean
     */
    public function isString();
}
