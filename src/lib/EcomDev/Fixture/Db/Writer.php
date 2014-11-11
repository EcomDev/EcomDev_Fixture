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
use EcomDev_Fixture_Contract_Db_Writer_ContainerInterface as ContainerInterface;

use EcomDev_Fixture_Db_Resolver as Resolver;
use EcomDev_Fixture_Db_Writer_Container as Container;

class EcomDev_Fixture_Db_Writer 
    implements EcomDev_Fixture_Contract_Db_WriterInterface
{    
    /**
     * Database adapter
     *
     * @var AdapterInterface|Zend_Db_Adapter_Abstract
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
     * Container object
     * 
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Insert operations batch size
     * 
     * @var int
     */
    protected $batchSize = self::DEFAULT_BATCH_SIZE;

    /**
     * List of errors that appeared during flush process
     * 
     * @var ErrorInterface[]
     */
    protected $errors = array();

    /**
     * Information about amount of processed records, during current flush
     * 
     * @var array
     */
    protected $stats = array(
        ContainerInterface::TYPE_INSERT => 0,
        ContainerInterface::TYPE_UPDATE => 0,
        ContainerInterface::TYPE_DELETE => 0
    );
    
    /**
     * Constructor with dependencies passed
     *
     * @param AdapterInterface $adapter
     * @param SchemaInterface $schema
     * @param ResolverInterface $resolver
     */
    public function __construct(AdapterInterface $adapter, SchemaInterface $schema, 
                                ResolverInterface $resolver = null, ContainerInterface $container = null)
    {
        if ($resolver === null) {
            $resolver = new Resolver($adapter, $schema);
        }
        
        if ($container === null) {
            $container = new Container($schema, $resolver);
        }
        
        $this->adapter = $adapter;
        $this->schema = $schema;
        $this->resolver = $resolver;
        $this->container = $container;
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
     * Flushes scheduled items into database
     *
     * @return $this
     * @throws Exception if any exception was thrown during flush process
     */
    public function flush()
    {
        $this->errors = array();
        $this->resetStats();
        
        $this->adapter->beginTransaction();
        try {
            $queues = array(ContainerInterface::QUEUE_PRIMARY, ContainerInterface::QUEUE_SECONDARY);
            foreach ($queues as $queue) {
                $this->flushDelete($queue);
                $this->flushInsert($queue);
                $this->flushUpdate($queue);
            }
            $this->adapter->commit();
        } catch (Exception $e) {
            $this->resetStats();
            $this->adapter->rollBack();
            throw $e;
        }
        
        return $this;
    }

    /**
     * Resets insert/update/delete statistics
     * 
     * @return $this
     */
    protected function resetStats()
    {
        $this->stats[ContainerInterface::TYPE_INSERT] = 0;
        $this->stats[ContainerInterface::TYPE_UPDATE] = 0;
        $this->stats[ContainerInterface::TYPE_DELETE] = 0;
        return $this;
    }

    /**
     * Checks if there were any errors during flush operation
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Returns all flush errors
     *
     * @return ErrorInterface[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets container instance for a writer
     *
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Schedules an insert operation
     *
     * @param string $table
     * @param array $data
     * @param int $queue
     * @return $this
     */
    public function scheduleInsert($table, $data, $queue = ContainerInterface::QUEUE_PRIMARY)
    {
        $this->container->scheduleInsert($table, $data, $queue);
        return $this;
    }

    /**
     * Schedules an update operation
     *
     * @param $table
     * @param array $data
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleUpdate($table, $data, $condition = array(), $queue = ContainerInterface::QUEUE_PRIMARY)
    {
        $this->container->scheduleUpdate($table, $data, $condition, $queue);
        return $this;
    }

    /**
     * Schedules a delete operation
     *
     * @param string $table
     * @param array $condition
     * @param int $queue
     * @return $this
     */
    public function scheduleDelete($table, $condition = array(), $queue = ContainerInterface::QUEUE_PRIMARY)
    {
        $this->container->scheduleDelete($table, $condition, $queue);
        return $this;
    }

    /**
     * Flushes insert schedule for a table
     * 
     * @param int $queue
     * @param string $tableName
     * @return $this
     */
    public function flushInsertSchedule($queue, $tableName)
    {
        $table = $this->schema->getTableInfo($tableName);
        $primaryKeyColumn = $table->getPrimaryKeyColumn();
        $hasErrors = false;
        while ($this->container->hasSchedule($queue, $tableName, ContainerInterface::TYPE_INSERT)) {
            $this->resolver->resolve($tableName);
            $this->container->resolve();
            $isMultiple = $this->container->isInsertScheduleMultiple($queue, $tableName);
            $dataToInsert = $this->container->getSchedule($queue, $tableName, ContainerInterface::TYPE_INSERT);
            if (!$dataToInsert) {
                $hasErrors = true;
                break;
            }
            $numberOfRows = count($dataToInsert);
            
            if ($isMultiple) {
                if ($numberOfRows > $this->getBatchSize()) {
                    foreach (array_chunk($dataToInsert, $this->getBatchSize()) as $rows) {
                        $this->adapter->insertOnDuplicate($tableName, $rows);
                    }
                } else {
                    $this->adapter->insertOnDuplicate($tableName, $dataToInsert);
                }
            } else {
                foreach ($dataToInsert as $rowIndex => $data) {
                    $primaryMap = $this->getContainer()->getInsertSchedulePrimaryKeyMap($queue, $tableName, $rowIndex);
                    if ($primaryMap && $primaryKeyColumn instanceof ColumnInterface) {
                        $data[$primaryKeyColumn->getName()] = null;
                    }
                    $this->adapter->insertOnDuplicate($tableName, $data);
                    if ($primaryMap) {
                        $primaryMap->setValue($this->adapter->lastInsertId($tableName));
                    }
                }
            }
            
            $this->stats[ContainerInterface::TYPE_INSERT] += $numberOfRows;
        }
        
        if ($hasErrors) {
            $this->addErrorsFromContainer($queue, $tableName, ContainerInterface::TYPE_INSERT);
        }

        $this->resolver->resolve($tableName);
        return $this;
    }

    /**
     * Flushes update schedule for a table
     *
     * @param int $queue
     * @param string $tableName
     * @return $this
     */
    public function flushUpdateSchedule($queue, $tableName)
    {
        $hasErrors = false;
        while ($this->container->hasSchedule($queue, $tableName, ContainerInterface::TYPE_UPDATE)) {
            $this->resolver->resolve($tableName);
            $this->container->resolve();
            $updates = $this->container->getSchedule($queue, $tableName, ContainerInterface::TYPE_UPDATE);
            if (!$updates) {
                $hasErrors = true;
                break;
            }
            foreach ($updates as $update) {
                if ($update['condition']) {
                    $this->stats[ContainerInterface::TYPE_UPDATE] += $this->adapter->update($tableName, $update['data'], $update['condition']);
                } else {
                    $this->stats[ContainerInterface::TYPE_UPDATE] += $this->adapter->update($tableName, $update['data']);
                }
            }
        }

        if ($hasErrors) {
            $this->addErrorsFromContainer($queue, $tableName, ContainerInterface::TYPE_UPDATE);
        }
        
        $this->resolver->resolve($tableName);
        
        return $this;
    }
    
    /**
     * Flushes update schedule for a table
     *
     * @param int $queue
     * @param string $tableName
     * @return $this
     */
    public function flushDeleteSchedule($queue, $tableName)
    {
        $hasErrors = false;
        while ($this->container->hasSchedule($queue, $tableName, ContainerInterface::TYPE_DELETE)) {
            $this->resolver->resolve($tableName);
            $this->container->resolve();
            $delete = $this->container->getSchedule($queue, $tableName, ContainerInterface::TYPE_DELETE);
            if (!$delete) {
                $hasErrors = true;
                break;
            }
            foreach ($delete as $item) {
                if ($item) {
                    $this->stats[ContainerInterface::TYPE_DELETE] += $this->adapter->delete($tableName, $item);
                } else {
                    $this->stats[ContainerInterface::TYPE_DELETE] += $this->adapter->delete($tableName);
                }
            }
        }

        if ($hasErrors) {
            $this->addErrorsFromContainer($queue, $tableName, ContainerInterface::TYPE_DELETE);
        }
        
        $this->resolver->resolve($tableName);
        
        return $this;
    }

    /**
     * Flushes queue for delete operations
     * 
     * @param int $queue
     * @return $this
     */
    public function flushDelete($queue)
    {
        foreach ($this->container->getScheduleTables($queue, ContainerInterface::TYPE_DELETE) as $tableName) {
            $this->flushDeleteSchedule($queue, $tableName);
        }
        return $this;
    }

    /**
     * Flushes queue for update operations
     * 
     * @param int $queue
     * @return $this
     */
    public function flushUpdate($queue)
    {
        foreach ($this->container->getScheduleTables($queue, ContainerInterface::TYPE_UPDATE) as $tableName) {
            $this->flushUpdateSchedule($queue, $tableName);
        }
        return $this;
    }

    /**
     * Flushes queue for insert operations
     * 
     * @param int $queue
     * @return $this
     */
    public function flushInsert($queue)
    {
        foreach ($this->container->getScheduleTables($queue, ContainerInterface::TYPE_INSERT) as $tableName) {
            $this->flushInsertSchedule($queue, $tableName);
        }

        return $this;
    }

    /**
     * Adds errors from container
     * 
     * @param int $queue
     * @param string $tableName
     * @param string $type
     * @return $this
     */
    protected function addErrorsFromContainer($queue, $tableName, $type)
    {
        $errors = $this->container->getScheduleResolveErrors($queue, $tableName, $type);
        foreach ($errors as $error) {
            $this->errors[] = $error;
        }
        return $this;
    }
    
    /**
     * Retrieves batch size for mass operations
     *
     * @return $this
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * Sets batch size for mass operations
     *
     * @param int $batchSize
     * @return $this
     */
    public function setBatchSize($batchSize)
    {
        if ((int)$batchSize) {
            $this->batchSize = (int)$batchSize;
        }
        return $this;
    }

    /**
     * Returns array with statistics of the writer process
     *
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }
}
