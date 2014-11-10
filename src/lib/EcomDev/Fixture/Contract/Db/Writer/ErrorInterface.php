<?php

use EcomDev_Fixture_Contract_Db_Writer_ContainerInterface as ContainerInterface;

/**
 * Error interface for writer error messages
 * 
 */
interface EcomDev_Fixture_Contract_Db_Writer_ErrorInterface
{
    const TYPE_INSERT = ContainerInterface::TYPE_INSERT;
    const TYPE_UPDATE = ContainerInterface::TYPE_UPDATE;
    const TYPE_DELETE = ContainerInterface::TYPE_DELETE;
    
    const QUEUE_PRIMARY = ContainerInterface::QUEUE_PRIMARY;
    const QUEUE_SECONDARY = ContainerInterface::QUEUE_SECONDARY;

    /**
     * @param string $message
     * @param string $type
     * @param string $table
     * @param int $queue
     * @param array $data
     */
    public function __construct(
        $message, $type, $table, 
        $queue = self::QUEUE_PRIMARY,
        $rowIndex = null,
        array $data = array()
    );

    /**
     * Message of the error
     * 
     * @return string
     */
    public function getMessage();

    /**
     * Type of the error
     * 
     * @return string
     */
    public function getType();

    /**
     * Table to which the error is related
     * 
     * @return string
     */
    public function getTable();

    /**
     * Queue number
     * 
     * @return int
     */
    public function getQueue();

    /**
     * Returns row index of the error message
     *
     * @return int|null
     */
    public function getRowIndex();
    
    /**
     * Data of the error message
     * 
     * @return array
     */
    public function getData();
}