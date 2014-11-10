<?php

/**
 * Notifier instance used for notifications management
 *
 */
interface EcomDev_Fixture_Contract_Utility_NotifierInterface
{
    public function notify($object, $operation, $data = null);
}
