<?php

/**
 * Error message instance for writer
 * 
 */
class EcomDev_Fixture_Db_Writer_Error
    implements EcomDev_Fixture_Contract_Db_Writer_ErrorInterface
{
    /**
     * Error message
     * 
     * @var string
     */
    protected $message;

    /**
     * Type of row
     * 
     * @var string
     */
    protected $type;

    /**
     * Table to which row is related
     * 
     * @var string
     */
    protected $table;

    /**
     * Queue number
     * 
     * @var int
     */
    protected $queue;

    /**
     * Row identifier
     * 
     * @var int
     */
    protected $rowIndex;

    /**
     * Error data details
     * 
     * @var array
     */
    protected $data;

    /**
     * Constructs an error instance
     *
     * @param string $message
     * @param string $type
     * @param string $table
     * @param int $queue
     * @param int|null $rowIndex
     * @param array $data
     */
    public function __construct($message, $type, $table, $queue = self::QUEUE_PRIMARY, 
                                $rowIndex = null, array $data = array())
    {
        $this->message = $message;
        $this->type = $type;
        $this->table = $table;
        $this->queue = $queue;
        $this->rowIndex = $rowIndex;
        $this->data = $data;
    }

    /**
     * Message of the error
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Type of the error
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Queue number
     *
     * @return int
     */
    public function getQueue()
    {
        return $this->queue;
    }
    
    /**
     * Table to which the error is related
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns row index of the error message
     *
     * @return int|null
     */
    public function getRowIndex()
    {
        return $this->rowIndex;
    }
    
    /**
     * Data of the error message
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
