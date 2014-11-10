<?php

/**
 * Exception to catch the wrong arguments or data errors
 */
class EcomDev_Fixture_Db_Schema_Exception extends RuntimeException
{
    const TABLE_NOT_FOUND = 1;
    const NO_ADAPTER = 2;
}