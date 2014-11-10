<?php

/**
 * Notifier aware interface. 
 * 
 * Used to designate objects, that send notifications
 */
interface EcomDev_Fixture_Contract_Utility_NotifierAwareInterface
{
    /**
     * Sets notifier instance
     * 
     * @param EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier
     * @return $this
     */
    public function addNotifier(EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier);

    /**
     * Removes a notifier
     * 
     * @param EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier
     * @return $this
     */
    public function removeNotifier(EcomDev_Fixture_Contract_Utility_NotifierInterface $notifier);

    /**
     * Returns list of notifiers
     * 
     * @return EcomDev_Fixture_Contract_Utility_NotifierInterface[]
     */
    public function getNotifiers();
}
