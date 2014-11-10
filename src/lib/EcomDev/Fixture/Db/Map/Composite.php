<?php

class EcomDev_Fixture_Db_Map_Composite
    extends EcomDev_Fixture_Db_AbstractMap
    implements EcomDev_Fixture_Contract_Db_Map_CompositeInterface
{
    /**
     * List of maps
     * 
     * @var \EcomDev_Fixture_Contract_Db_MapInterface[]
     */
    protected $maps = array();

    /**
     * Separator of generated values from all child items
     * 
     * @var string
     */
    protected $separator = '';
    
    /**
     * Makes table not required in the constructor
     * 
     * @param string|null $table
     */
    public function __construct($table = null)
    {
        parent::__construct($table);
    }
    
    /**
     * Should return all the child assigned maps
     *
     * @return \EcomDev_Fixture_Contract_Db_MapInterface[]
     */
    public function getMaps()
    {
        return $this->maps;
    }

    /**
     * Add another map into composite list
     *
     * @param EcomDev_Fixture_Contract_Db_MapInterface $map
     * @return $this
     */
    public function addMap(EcomDev_Fixture_Contract_Db_MapInterface $map)
    {
        $this->maps[] = $map;
        return $this;
    }

    /**
     * Sets separator to produce correct getValue() output
     *
     * @param string $separator
     * @return $this
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Returns true if 
     * 
     * @return bool
     */
    public function isResolved()
    {
        $isResolved = true;
        
        foreach ($this->maps as $map) {
            $isResolved = $isResolved && $map->isResolved();
        }
        
        return $isResolved;
    }

    /**
     * Returns value for mapping
     * 
     * @return null|string
     */
    public function getValue()
    {
        if ($this->value !== null || !$this->isResolved()) {
            return $this->value;
        }
        
        $value = array();
        foreach ($this->maps as $map) {
            $value[] = $map->getValue();
        }
        
        $value = implode($this->separator, $value);
        
        $this->setValue($value);
        return $value;
    }
}
