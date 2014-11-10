<?php

class EcomDev_Fixture_Db_Schema_Column 
    implements EcomDev_Fixture_Contract_Db_Schema_ColumnInterface
{
    /**
     * Column name
     * 
     * @var string
     */
    protected $name;

    /**
     * Column type
     * 
     * @var string
     */
    protected $type;

    /**
     * Default column value
     * 
     * @var null|string
     */
    protected $defaultValue;

    /**
     * Column max length
     * 
     * @var int|null
     */
    protected $length;

    /**
     * Column scale (decimals only)
     * 
     * @var int|null
     */
    protected $scale;

    /**
     * Bitmask for NULLABLE, PRIMARY, IDENTIFIER options 
     * 
     * @var int
     */
    protected $options;
    
    /**
     * Constructor
     * 
     * @param string $name
     * @param string $type
     * @param string $defaultValue
     * @param int|null $length
     * @param int|null $scale
     * @param int $options
     */
    public function __construct($name, $type, $defaultValue = null, $length = null, $scale = null, $options = 0)
    {
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->length = $length;
        $this->scale = $scale;
        $this->options = $options;
    }

    /**
     * Returns name of the column
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns type of the column
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns length of the column
     *
     * @return int|null
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Returns scale of the column if applicable
     *
     * @return int|null
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Returns default value of the column
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Returns options
     *
     * @return int
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns boolean flag if option is in bitmask
     * 
     * @param int $option
     * @return bool
     */
    protected function isOption($option)
    {
        return ($this->getOptions() & $option) === $option;
    }
    
    /**
     * Returns flag for nullable option
     *
     * @return boolean
     */
    public function isNullable()
    {
        return $this->isOption(self::OPTION_NULLABLE);
    }

    /**
     * Returns flag for unsigned option
     *
     * @return boolean
     */
    public function isUnsigned()
    {
        return $this->isOption(self::OPTION_UNSIGNED);
    }

    /**
     * Returns flag for primary option
     *
     * @return boolean
     */
    public function isPrimary()
    {
        return $this->isOption(self::OPTION_PRIMARY);
    }

    /**
     * Returns flag for identity option
     *
     * @return boolean
     */
    public function isIdentity()
    {
        return $this->isOption(self::OPTION_IDENTITY);
    }

    /**
     * Returns flag for integer type
     *
     * @return boolean
     */
    public function isInteger()
    {
        return in_array(
            $this->getType(), 
            array(
                self::TYPE_TINYINT, 
                self::TYPE_SMALLINT,
                self::TYPE_BIGINT, 
                self::TYPE_INTEGER
            ),
            true
        );
    }

    /**
     * Returns flag for decimal type
     *
     * @return boolean
     */
    public function isDecimal()
    {
        return in_array(
            $this->getType(),
            array(
                self::TYPE_DECIMAL,
                self::TYPE_REAL,
                self::TYPE_FLOAT,
                self::TYPE_NUMERIC,
                self::TYPE_DOUBLE
            ),
            true
        );
    }

    /**
     * Returns flag for date based type
     *
     * @return bool
     */
    public function isDateBased()
    {
        return in_array(
            $this->getType(),
            array(
                self::TYPE_DATE,
                self::TYPE_DATETIME,
                self::TYPE_TIMESTAMP,
                self::TYPE_TIME
            ),
            true
        );
    }

    /**
     * Returns flag for data that is a string value
     *
     * @return boolean
     */
    public function isString()
    {
        return in_array(
            $this->getType(),
            array(
                self::TYPE_CHAR,
                self::TYPE_VARCHAR,
                self::TYPE_TEXT,
                self::TYPE_TINYTEXT,
                self::TYPE_MEDIUMTEXT,
                self::TYPE_LONGTEXT
            ),
            true
        );
    }

    /**
     * Returns recommended value for a database column based on column metadata
     * 
     * @param string $value
     * @return null|string
     */
    public function getRecommendedValue($value)
    {
        if ($this->isInteger()) {
            return $this->getRecommendedInteger($value);
        } elseif ($this->isDecimal()) {
            return $this->getRecommendedDecimal($value);
        } elseif ($this->isDateBased()) {
            return $this->getRecommendedDateBased($value);          
        } elseif ($this->isString()) {
            if ($value === null) {
                if (!$this->isNullable()) {
                    $value = $this->defaultValue !== null ? $this->defaultValue : '';
                }
            } elseif ($this->getLength() && iconv_strlen($value, 'UTF-8') > $this->getLength()) {
                $value = iconv_substr($value, 0, $this->getLength(), 'UTF-8');
            }
        }
        
        return $value;
    }

    /**
     * Returns recommended value for integer column
     * 
     * @param int|string|null $value
     * @return null|string
     */
    protected function getRecommendedInteger($value) 
    {
        if ($value === '' || $value === null) {
            if ($this->isNullable()) {
                return null;
            } elseif ($this->defaultValue !== null) {
                return $this->defaultValue;
            } else {
                return '0';
            }
        }

        return (string)(int)$value;
    }

    /**
     * Returns recommended decimal value that is stored in the database
     * 
     * @param float|int|string|null $value
     * @return int|null|string
     */
    protected function getRecommendedDecimal($value)
    {
        if ($value === '' || $value === null) {
            if ($this->isNullable()) {
                return null;
            } elseif ($this->defaultValue !== null) {
                $value = $this->defaultValue;
            } else {
                $value = 0;
            }
        }

        if (is_string($value)) {
            return $value;
        }

        return number_format($value, $this->getScale(), '.', '');
    }

    /**
     * Returns date based value format
     * 
     * @param string|DateTime|null $value
     * @return string
     */
    protected function getRecommendedDateBased($value)
    {
        if ($value === '' || $value === null) {
            if ($this->isNullable()) {
                return null;
            }

            $defaultValueType = array(
                self::TYPE_DATE => '0000-00-00',
                self::TYPE_TIME => '00:00:00'
            );

            if (isset($defaultValueType[$this->getType()])) {
                $value = $defaultValueType[$this->getType()];
            } else {
                $value = '0000-00-00 00:00:00';
            }
        }

        if ($value instanceof DateTime) {
            $format = 'Y-m-d H:i:s';
            if ($this->getType() == self::TYPE_TIME) {
                $format = 'H:i:s';
            } elseif ($this->getType() == self::TYPE_DATE) {
                $format = 'Y-m-d';
            }

            return $value->format($format);
        }

        return $value;
    }
}
