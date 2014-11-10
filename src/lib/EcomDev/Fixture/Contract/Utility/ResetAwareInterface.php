<?php

/**
 * Interface for reset aware objects. 
 * 
 * This interface should be implemented to correctly clear 
 * all possible external references
 */
interface EcomDev_Fixture_Contract_Utility_ResetAwareInterface
{
    /**
     * Resets object state
     * 
     * @return $this
     */
    public function reset();
}
