<?php

use EcomDev_Fixture_Contract_Utility_NotifierInterface as NotifierInterface;

/**
 * Container for notifier
 * 
 */
class EcomDev_Fixture_Utility_Notifier_Container
{
    /**
     * List of notifier containers
     * 
     * @var NotifierInterface[]
     */
    protected $notifiers = array();

    /**
     * Adds a new notifier to list of notifiers
     * 
     * @param NotifierInterface $notifier
     * @return $this
     */
    public function add(NotifierInterface $notifier)
    {
        $this->notifiers[spl_object_hash($notifier)] = $notifier;
        return $this;
    }

    /**
     * Removes a notifier by its key
     * 
     * @param NotifierInterface $notifier
     * @return $this
     */
    public function remove(NotifierInterface $notifier)
    {
        $objectId = spl_object_hash($notifier);
        if (isset($this->notifiers[$objectId])) {
            unset($this->notifiers[$objectId]);
        }
        return $this;
    }

    /**
     * Returns true if no notifiers are added
     * 
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->notifiers);
    }

    /**
     * Notifies all notifier objects
     * 
     * @param $object
     * @param $operation
     * @param mixed $data
     * @return $this
     */
    public function notify($object, $operation, $data = null)
    {
        if ($this->isEmpty()) {
            return $this;
        }
        
        foreach ($this->notifiers as $notifier) {
            $notifier->notify($object, $operation, $data);
        }
        
        return $this;
    }

    /**
     * Returns all notifiers
     * 
     * @return NotifierInterface[]
     */
    public function items()
    {
        return array_values($this->notifiers);
    }
}