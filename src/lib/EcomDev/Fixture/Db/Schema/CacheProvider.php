<?php

class EcomDev_Fixture_Db_Schema_CacheProvider
    implements EcomDev_Fixture_Contract_Db_Schema_CacheProviderInterface,
               EcomDev_Fixture_Contract_Db_AdapterAwareInterface
{
    const CACHE_DB_CHECKSUM = 'ECOMDEV_FIXTURE_SCHEMA_%s_CHECKSUM';
    const CACHE_DB_TABLES = 'ECOMDEV_FIXTURE_SCHEMA_%s_TABLES';
    
    /**
     * Db adapter
     * 
     * @var Varien_Db_Adapter_Interface
     */
    protected $adapter;

    /**
     * Db data checksum
     * 
     * @var string
     */
    protected $checksum;

    /**
     * Cache adapter
     * 
     * @var Zend_Cache_Core
     */
    protected $cacheAdapter;

    /**
     * Retrieves tables
     * 
     * @return array
     */
    public function getTables()
    {
        $cacheData = $this->cacheAdapter->load(
            sprintf(self::CACHE_DB_TABLES, $this->getDbName())
        );
        
        $tables = array();
        
        if ($cacheData) {
            $tables = unserialize($cacheData);
        }
        
        return $tables;
    }

    /**
     * Sets tables
     * 
     * @param array $tables
     * @return $this
     */
    public function setTables($tables)
    {
        $this->cacheAdapter->save(
            serialize($tables),
            sprintf(self::CACHE_DB_TABLES, $this->getDbName())
        );

        $this->cacheAdapter->save(
            $this->getChecksum(),
            sprintf(self::CACHE_DB_CHECKSUM, $this->getDbName())
        );
        
        return $this;
    }

    /**
     * Returns true if checksum stored in cache is equal 
     * to current database checksum
     * 
     * @return bool
     */
    public function isValid()
    {
        if ($this->getAdapter() === null) {
            throw new EcomDev_Fixture_Db_Schema_Exception(
                'Database adapter should be specified', 
                EcomDev_Fixture_Db_Schema_Exception::NO_ADAPTER
            );
        }

        if ($this->cacheAdapter === null) {
            throw new RuntimeException(
                'Cache adapter should be specified'
            );
        }

        $dbName = $this->getDbName();
        $cacheChecksum = $this->cacheAdapter->load(sprintf(self::CACHE_DB_CHECKSUM, $dbName));
        if ($cacheChecksum !== $this->getChecksum() 
            || !$this->cacheAdapter->load(sprintf(self::CACHE_DB_TABLES, $dbName))) {
            return false;
        }
        
        return true;
    }

    /**
     * Returns a database adapter
     * 
     * @return Varien_Db_Adapter_Interface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Sets an adapter to a cache provider
     * 
     * @param Varien_Db_Adapter_Interface $adapter
     * @return $this
     */
    public function setAdapter(Varien_Db_Adapter_Interface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * A cache core instance 
     * 
     * @param Zend_Cache_Core $cacheAdapter
     * @return $this
     */
    public function setCacheAdapter($cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
        return $this;
    }

    /**
     * Returns a checksum of the database
     * 
     * @return string
     */
    public function getChecksum()
    {
        if ($this->checksum === null) {
            $dbName = $this->getDbName();
            $selectsToHash = array();
            // Select for columns in all the tables
            $selectsToHash[] = $this->getAdapter()->select()
                    ->from('COLUMNS', array(
                        'table' => 'TABLE_NAME',
                        'column' => 'COLUMN_NAME',
                        'type' => 'COLUMN_TYPE',
                        'default' => 'COLUMN_DEFAULT',
                        'key' => 'COLUMN_KEY',
                        'extra' => 'EXTRA'
                    ), 'information_schema')
                    ->where('TABLE_SCHEMA = ?', $dbName);
            // Select for constraints and columns
            $selectsToHash[] = $this->getAdapter()->select()
                ->from('KEY_COLUMN_USAGE', array(
                    'name' => 'CONSTRAINT_NAME',
                    'table' => 'TABLE_NAME',
                    'column' => 'COLUMN_NAME',
                    'position' => 'ORDINAL_POSITION',
                    'key_position' => 'POSITION_IN_UNIQUE_CONSTRAINT',
                    'reference_table' => 'REFERENCED_TABLE_NAME',
                    'reference_column' => 'REFERENCED_COLUMN_NAME'
                ), 'information_schema')
                ->where('TABLE_SCHEMA = ?', $dbName);
            
            $hashes = array();
            
            foreach ($selectsToHash as $select) {
                $data = $this->getAdapter()->fetchAll($select);
                $hashes[] = md5(serialize($data));
            }
            
            $this->checksum = implode('|', $hashes);
        }

        return $this->checksum;
    }
    
    protected function getDbName()
    {
        $dbConfig = $this->getAdapter()->getConfig();
        return $dbConfig['dbname'];
    }
}
