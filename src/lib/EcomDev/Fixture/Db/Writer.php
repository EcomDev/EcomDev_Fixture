<?php

use Varien_Db_Adapter_Interface as AdapterInterface;
use EcomDev_Fixture_Contract_Db_SchemaInterface as SchemaInterface;
use EcomDev_Fixture_Contract_Db_Schema_TableInterface as TableInterface;
use EcomDev_Fixture_Contract_Db_Schema_ColumnInterface as ColumnInterface;
use EcomDev_Fixture_Contract_Db_ResolverInterface as ResolverInterface;
use EcomDev_Fixture_Contract_Db_MapInterface as MapInterface;
use EcomDev_Fixture_Contract_Db_Map_StaticInterface as StaticMapInterface;
use EcomDev_Fixture_Contract_Db_Resolver_MapInterface as ResolvableMapInterface;
use EcomDev_Fixture_Contract_Db_Writer_ErrorInterface as ErrorInterface;
use EcomDev_Fixture_Db_Resolver as Resolver;

class EcomDev_Fixture_Db_Writer 
    implements EcomDev_Fixture_Contract_Db_WriterInterface
{
    /**
     * Type of insert that is going to handle row inserts in batch
     * 
     * @var string
     */
    const INSERT_TYPE_DEFAULT = 'default';
    
    /**
     * Type of insert, that is going to handle row inserts row by row
     * 
     * @var string
     */
    const INSERT_TYPE_SINGLE = 'single';
    
    /**
     * Database adapter
     *
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Schema object
     *
     * @var SchemaInterface
     */
    protected $schema;

    /**
     * Resolver object
     *
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * Schedule for a writer
     * 
     * @var array
     */
    protected $schedule = array();

    /**
     * Type of insert operations
     * for tables
     * 
     * @var array
     */
    protected $insertType = array();
    
    /**
     * Constructor with dependencies passed
     *
     * @param AdapterInterface $adapter
     * @param SchemaInterface $schema
     * @param ResolverInterface $resolver
     */
    public function __construct(AdapterInterface $adapter, SchemaInterface $schema, ResolverInterface $resolver = null)
    {
        if ($resolver === null) {
            $resolver = new Resolver($adapter, $schema);
        }
        
        $this->adapter = $adapter;
        $this->schema = $schema;
        $this->resolver = $resolver;
    }


    /**
     * Returns a database adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Returns a database schema
     *
     * @return SchemaInterface
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Returns resolver
     *
     * @return ResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Registers a row in the resolver
     * 
     * @param string $table
     * @param array $row
     * @return ResolvableMapInterface|bool
     */
    public function registerRow($table, $row)
    {
        if ($this->getResolver()->canMapRow($table)) {
            return $this->getResolver()->mapRow($table, $row);
        }
        
        return false;
    }

    /**
     * @param TableInterface $table
     * @param array $row
     * @return array
     */
    public function processRow(TableInterface $table, $row)
    {
        $dataRow = array();
        
        foreach ($table->getColumns() as $column) {
            $value = null;
            if ($column->isPrimary()) {
                if (!isset($row[$column->getName()]) && !$column->isIdentity()) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'The primary key for "%s" is required, since it is not autoincrement based.',
                            $table->getName()
                        )
                    );
                } elseif (isset($row[$column->getName()])) {
                    $value = $row[$column->getName()];
                }
            } elseif (isset($row[$column->getName()])) {
                $value = $row[$column->getName()];
                
                if (is_array($value)) {
                    if (isset($value[self::VALUE_SERIALIZED])) {
                        $value = serialize($value[self::VALUE_SERIALIZED]);
                    } elseif (isset($value[self::VALUE_JSON])) {
                        $value = json_encode($value[self::VALUE_JSON]);
                    } else {
                        throw new InvalidArgumentException(
                            sprintf(
                                'Invalid value supplied for "%s" in "%s". Supplied value is "%"',
                                $column->getName(),
                                $table->getName(),
                                print_r($value, true)
                            )
                        );
                    }
                } elseif (!$value instanceof MapInterface) {
                    $value = $column->getRecommendedValue($value);
                }
            } else {
                $value = $column->getRecommendedValue(null);
            }
            
            $dataRow[$column->getName()] = $value;
        }
        
        return $dataRow;
    }

    /**
     * Returns a column value prepared for a database insert
     * 
     * @param TableInterface $table
     * @param ColumnInterface $column
     * @param mixed $value
     * @return string|MapInterface|null
     */
    protected function prepareColumnValue(TableInterface $table, ColumnInterface $column, $value)
    {
        if (is_array($value)) {
            if (isset($value[self::VALUE_SERIALIZED])) {
                $value = serialize($value[self::VALUE_SERIALIZED]);
            } elseif (isset($value[self::VALUE_JSON])) {
                $value = json_encode($value[self::VALUE_JSON]);
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid value supplied for "%s" in "%s". Supplied value is "%"',
                        $column->getName(),
                        $table->getName(),
                        print_r($value, true)
                    )
                );
            }
        } elseif (!$value instanceof MapInterface) {
            $value = $column->getRecommendedValue($value);
        }
        
        return $value;
    }
    
    public function detectInsertType(TableInterface $table, $row)
    {
        if (!isset($this->insertType[$table->getName()])) {
            $this->insertType[$table->getName()] = self::INSERT_TYPE_DEFAULT;
        }
        
        $primaryKeyColumn = $table->getPrimaryKeyColumn();
        
        if ($primaryKeyColumn instanceof ColumnInterface 
            && isset($row[$primaryKeyColumn->getName()]) 
            && $row[$primaryKeyColumn->getName()] instanceof StaticMapInterface) {
            $this->insertType[$table->getName()] = self::INSERT_TYPE_SINGLE;
        }
    }

    /**
     * Schedules an insert into the table
     *
     * This method should handle duplicated entries with existing database records
     *
     * @param string $table
     * @param array $row
     * @return $this
     */
    public function schedule($table, array $row)
    {
        
    }
    
    

    /**
     * Flushes scheduled items into database
     *
     * @return $this
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * Checks if there were any errors during flush operation
     *
     * @return bool
     */
    public function hasErrors()
    {
        // TODO: Implement hasErrors() method.
    }

    /**
     * Returns all flush errors
     *
     * @return ErrorInterface[]
     */
    public function getErrors()
    {
        // TODO: Implement getErrors() method.
    }
}
