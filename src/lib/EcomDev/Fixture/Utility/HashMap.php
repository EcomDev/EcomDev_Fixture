<?php

/**
 * Hash map implementation for PHP
 */
class EcomDev_Fixture_Utility_HashMap implements ArrayAccess
{
    /**
     * Option to return only value in iteration calls
     * 
     * @var int
     */
    const OPTION_VALUE = 1;

    /**
     * Option to return only key in iteration calls
     * 
     * @var int
     */
    const OPTION_KEY = 2;

    /**
     * Option to return key and value as function arguments
     *
     * @var int
     */
    const OPTION_PAIR = 3;
    
    /**
     * Map of hash values
     * 
     * @var mixed[]
     */
    protected $values = array();

    /**
     * Map of hash keys
     * 
     * @var mixed[]
     */
    protected $keys = array();

    /**
     * Hashes objects and arrays for making possible use of array/object keys 
     * 
     * @param string|array|object $key
     * @return string
     */
    protected function keyHash($key)
    {
        if ($key === null) {
            throw new InvalidArgumentException('A hash map key cannot be null');
        }
        
        if (is_array($key)) {
            return json_encode($key, JSON_FORCE_OBJECT);
        } elseif (is_object($key)) {
            return spl_object_hash($key);
        }
        
        return $key;
    }
    
    /**
     * Checks if object/array exists in hash map
     * 
     * @param string|array|object $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->keys[$this->keyHash($offset)]);
    }

    /**
     * Returns a mapped value for a key
     *
     * @param string|array|object $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $key = $this->keyHash($offset);
        
        if (!isset($this->values[$key])) {
            throw new InvalidArgumentException('A hash map key does not exists');
        }
        
        return $this->values[$key];
    }

    /**
     * Sets a mapped value for a key
     *
     * @param string|array|object $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $key = $this->keyHash($offset);
        $this->values[$key] = $value;
        $this->keys[$key] = $offset;
    }

    /**
     * Removes a mapped key from hash map
     *
     * @param string|array|object $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $key = $this->keyHash($offset);
        unset($this->keys[$key]);
        unset($this->values[$key]);
    }

    /**
     * Iterates over all 
     * 
     * @param callable $block
     * @param int $option option for iterator, which arguments to pass
     * @return $this
     */
    public function each($block, $option = self::OPTION_PAIR) 
    {
        foreach ($this->keys as $hash => $key) {
            $value = $this->values[$hash];
            $this->invokeItemCallback($block, $option, $key, $value);
        }

        return $this;
    }

    /**
     * Returns filtered hash map values
     * 
     * @param callable $block
     * @param int $filterOption
     * @param null|int $returnOption
     * @return mixed[]
     */
    public function filter($block, $filterOption = self::OPTION_PAIR, $returnOption = null)
    {
        if ($returnOption === null) {
            $returnOption = $filterOption;
        }
        
        $result = array();
        
        foreach ($this->keys as $hash => $key) {
            $value = $this->values[$hash];
            if ($this->invokeItemCallback($block, $filterOption, $key, $value)) {
                if ($returnOption == self::OPTION_PAIR) {
                    $result[] = array($key, $value);
                } elseif ($returnOption == self::OPTION_KEY) {
                    $result[] = $key;
                } else {
                    $result[] = $value;
                }
            }
        }
        
        return $result;
    }

    /**
     * Iterates over every item and returns the result of mapping
     * 
     * @param $block
     * @param int $option
     * @return mixed[]
     */
    public function map($block, $option = self::OPTION_PAIR, $filterBlock = null, $filterOption = null)
    {
        $result = array();
        
        if ($filterOption === null) {
            $filterOption = $option;
        }
        
        foreach ($this->keys as $hash => $key) {
            $value = $this->values[$hash];
            if ($filterBlock !== null && !$this->invokeItemCallback($filterBlock, $filterOption, $key, $value)) {
                continue;
            }
            $result[] = $this->invokeItemCallback($block, $option, $key, $value);
        }
        
        return $result;
    }

    /**
     * @param $block
     * @param $option
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function invokeItemCallback($block, $option, $key, $value)
    {
        if ($option == self::OPTION_PAIR) {
            return $block($key, $value);
        } elseif ($option == self::OPTION_KEY) {
            return $block($key);
        }
        
        return $block($value);
    }

    /**
     * Resets the data of hash map
     * 
     * @return $this
     */
    public function reset()
    {
        $this->values = array();
        $this->keys = array();
        return $this;
    }
}
