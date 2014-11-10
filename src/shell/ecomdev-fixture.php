<?php
/**
 * PHP Unit test suite for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_PHPUnit
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */


require_once 'abstract.php';

use EcomDev_Fixture_Db_Schema as Schema;
use EcomDev_Fixture_Db_Schema_InformationProvider as SchemaInformationProvider;

/**
 * Fixtures shell interface
 *
 *
 */
class EcomDev_Fixture_Shell extends Mage_Shell_Abstract
{
    const SCHEMA_CACHE_KEY = 'ecomdev_fixture_shell_schema';
    
    protected $_action;
    
    protected $_info;
    
    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ecomdev-fixture.php -- <action> <options>

  -h --help                 Shows usage

Defined <action>s:

  schema:list           lists all the tables in Magento database
  schema:list:relation  lists all the tables sorted by relation and print its parents and child tables 

USAGE;
    }

    /**
     * Parse input arguments
     *
     * @return Mage_Shell_Abstract
     */
    protected function _parseArgs()
    {
        $current = null;
        foreach ($_SERVER['argv'] as $arg) {
            $match = array();
            if (preg_match('#^--([\w\d_-]{1,})$#', $arg, $match) || preg_match('#^-([\w\d_]{1,})$#', $arg, $match)) {
                $current = $match[1];
                $this->_args[$current] = true;
            } else {
                if ($current) {
                    $this->_args[$current] = $arg;
                } else if (preg_match('#^([\w\d\:_]{1,})$#', $arg, $match)) {
                    if ($this->_action === null) {
                        $this->_action = $match[1];
                    } else {
                        $this->_args[$match[1]] = true;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Runs scripts itself
     */
    public function run()
    {   
        if ($this->_action === null) {
            die($this->usageHelp());
        }
        
        $reflection = new ReflectionClass(__CLASS__);
        $methodName = '_run' . uc_words($this->_action, '', ':');
        if ($reflection->hasMethod($methodName)) {
            $this->$methodName();
        } else {
            die($this->usageHelp());
        }
    }
    
    protected function _getSchema()
    {
        if ($this->_info === null) {
            if ($data = Mage::app()->loadCache(self::SCHEMA_CACHE_KEY)) {
                $this->_info = unserialize($data);
            } else {
                $this->_info = new Schema(
                    new SchemaInformationProvider(
                        Mage::getSingleton('core/resource')->getConnection('core_setup')
                    )
                );
                
                Mage::app()->saveCache(
                    serialize($this->_info),
                    self::SCHEMA_CACHE_KEY,
                    array(
                        Mage_Core_Model_Config::CACHE_TAG
                    )
                );
            }
                        
        }
        
        return $this->_info;
    }

    protected function _runSchemaList()
    {
        $time = microtime(true);
        $tableNames = $this->_getSchema()->getTableNames();
        $this->_out('Fetched In:' . (microtime(true) - $time) . 's');
        $this->_outTable(array('Tables'), $tableNames);
    }

    protected function _runSchemaListRelation()
    {
        $time = microtime(true);
        $result = array();
        foreach ($this->_getSchema()->getTableNamesSortedByRelation() as $tableName) {
            $table = $this->_getSchema()->getTableInfo($tableName);
            $primary = array();
            
            if ($table->getPrimaryKeyColumn() === false) {
                $primaryType = 'None';
            } elseif (count($table->getPrimaryKeyColumn()) === 1) {
                $primaryType = 'Single';
                $primary[] = $table->getPrimaryKeyColumn()->getName();
            } else {
                $primaryType = 'Complex';
                $primary = array_keys($table->getPrimaryKeyColumn());
            }
            $result[] = array($tableName, $primaryType, implode(', ', $primary), implode(', ', $table->getParentTables()));
        }
        
        $this->_out('Fetched In:' . (microtime(true) - $time) . 's');
        $this->_outTable(array('Table Name', 'Primary Key Type', 'Primary Key Columns', 'Table Parents'), $result);
    }
    
    public function _outTable($columns, $rows, $limitWidth = 128)
    {
        $maxWidth = array();
        
        foreach ($columns as $columnIndex => $label) {
            $maxWidth[$columnIndex] =  min(strlen($label), $limitWidth);
        }
        

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $row = array($row);
            }
            
            foreach ($row as $columnIndex => $value) {
                $maxWidth[$columnIndex] = min(max($maxWidth[$columnIndex], strlen($value)), $limitWidth);
            }
        }
        
        ksort($maxWidth);
        
        $formatRow = function ($row) use ($maxWidth) {
            if (!is_array($row)) {
                $row = array($row);
            }
            $values = array();
            
            foreach ($maxWidth as $columnIndex => $width) {
                if (isset($row[$columnIndex])) {
                    $value = $row[$columnIndex];
                    if (strlen($value) > $width) {
                        $value = substr($value, 0, $width-3) . '...';
                    }
                } else {
                    $value = '';
                }
                
                $values[] = str_pad($value, $width, ' ');
            }
            return '| ' . implode($values, ' | ') . ' |';  
        };
        
        $this->_out(str_pad('-', 4 + (count($maxWidth)-1)*3 + array_sum($maxWidth), '-'));
        $this->_out($formatRow($columns));
        $this->_out(str_pad('-', 4 + (count($maxWidth)-1)*3 + array_sum($maxWidth), '-'));
        foreach ($rows as $row) {
            $this->_out($formatRow($row));
        }
        $this->_out(str_pad('-', 4 + (count($maxWidth)-1)*3 + array_sum($maxWidth), '-'));
    }
    
    /**
     * @param string $string
     * @return $this
     */
    protected function _out($string)
    {
        echo ' ' . $string . PHP_EOL;
        return $this;
    }
}

$shell = new EcomDev_Fixture_Shell();
$shell->run();
