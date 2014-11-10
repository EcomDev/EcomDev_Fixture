<?php

use EcomDev_Fixture_Contract_Db_Writer_ErrorInterface as ErrorInterface;

class EcomDev_FixtureTest_Test_Lib_Db_Writer_ErrorTest
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * @var EcomDev_Fixture_Db_Writer_Error
     */
    protected $error;
    
    protected function setUp()
    {
        $this->error = new EcomDev_Fixture_Db_Writer_Error(
            'Test message', ErrorInterface::TYPE_INSERT, 'table1', 
            ErrorInterface::QUEUE_PRIMARY, 0, array('some' => 'data')
        );
    }
    
    public function testItImplementsErrorInterface()
    {
        $this->assertInstanceOf(
            'EcomDev_Fixture_Contract_Db_Writer_ErrorInterface', 
            $this->error
        );
    }
    
    public function testItCorrectlyReturnsMessage()
    {
        $this->assertSame('Test message', $this->error->getMessage());
    }
    
    public function testItCorrectlyReturnsQueueNumber()
    {
        $this->assertSame(ErrorInterface::QUEUE_PRIMARY, $this->error->getQueue());
    }

    public function testItCorrectlyReturnsTableName()
    {
        $this->assertSame('table1', $this->error->getTable());
    }

    public function testItCorrectlyReturnsType()
    {
        $this->assertSame(ErrorInterface::TYPE_INSERT, $this->error->getType());
    }

    public function testItCorrectlyReturnsRowIndex()
    {
        $this->assertSame(0, $this->error->getRowIndex());
    }

    public function testItCorrectlyReturnsData()
    {
        $this->assertSame(array('some' => 'data'), $this->error->getData());
    }
}