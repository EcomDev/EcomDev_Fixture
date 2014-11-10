<?php

use EcomDev_Fixture_Contract_Utility_NotifierInterface as NotifierInterface;

/**
 * Implementation of basic interface of the map
 * 
 */
abstract class EcomDev_Fixture_Db_AbstractMap 
    implements EcomDev_Fixture_Contract_Db_MapInterface, 
               EcomDev_Fixture_Contract_Utility_NotifierAwareInterface,
               EcomDev_Fixture_Contract_Utility_ResetAwareInterface
{
    /**
     * Table to which the map is related
     * 
     * @var string
     */
    protected $table;

    /**
     * Value to which this map resolves 
     * 
     * @var string
     */
    protected $value;

    /**
     * Return an instance of notifier container
     * 
     * @var EcomDev_Fixture_Utility_Notifier_Container
     */
    protected $notifiers;

    /**
     * Instantiates a base map implementation
     * 
     * @param string $table
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * Table that is related to map
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets table property
     *
     * @param string $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }


    /**
     * Sets identifier, when it is known
     *
     * @param string|null $value
     * @return $this
     */
    public function setValue($value)
    {
        if ($this->notifiers) {
            $this->notifiers->notify($this, 'resolve', $value !== null);
        }
        
        $this->value = $value;
        return $this;
    }

    /**
     * Returns identifier, that was set by id resolver
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Is resolved flag, should return true if id is not equal to null
     *
     * @return boolean
     */
    public function isResolved()
    {
        return $this->getValue() !== null;
    }

    /**
     * String representation of getValue() call
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }

    /**
     * Sets notifier container from outside
     * 
     * @param EcomDev_Fixture_Utility_Notifier_Container $container
     * @return $this
     */
    public function setNotifierContainer(EcomDev_Fixture_Utility_Notifier_Container $container)
    {
        $this->notifiers = $container;
        return $this;
    }

    /**
     * Initializes empty notifier container
     * 
     * @return $this
     */
    protected function initNotifierContainer()
    {
        if ($this->notifiers === null) {
            $this->notifiers = new EcomDev_Fixture_Utility_Notifier_Container();
        }
        return $this;
    }
    
    /**
     * Sets notifier instance
     *
     * @param NotifierInterface $notifier
     * @return $this
     */
    public function addNotifier(NotifierInterface $notifier)
    {
        $this->initNotifierContainer();
        $this->notifiers->add($notifier);
        return $this;
    }

    /**
     * Removes a notifier
     *
     * @param NotifierInterface $notifier
     * @return $this
     */
    public function removeNotifier(NotifierInterface $notifier)
    {
        if ($this->notifiers) {
            $this->notifiers->remove($notifier);
            if ($this->notifiers->isEmpty()) {
                // Remove notifier if it is a last instance
                $this->notifiers = null;
            }
        }
        
        return $this;
    }

    /**
     * Returns list of notifiers
     *
     * @return NotifierInterface[]
     */
    public function getNotifiers()
    {
        if ($this->notifiers) {
            return $this->notifiers->items();
        }
        
        return array();
    }

    /**
     * Notifies other objects about reset operation
     * 
     * @return $this
     */
    public function reset()
    {
        if ($this->notifiers) {
            $this->notifiers->notify($this, 'reset');
        }
        return $this;
    }
}
