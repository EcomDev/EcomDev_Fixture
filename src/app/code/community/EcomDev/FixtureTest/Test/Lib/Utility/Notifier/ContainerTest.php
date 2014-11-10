<?php

class EcomDev_FixtureTest_Test_Lib_Utility_Notifier_ContainerTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * @var EcomDev_Fixture_Utility_Notifier_Container
     */
    protected $notifier;
    
    protected function setUp()
    {
        $this->notifier = new EcomDev_Fixture_Utility_Notifier_Container();
    }
    
    public function testItAddsNotifierAndUsesSplObjectIdAsKey()
    {
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $this->assertSame(
            $this->notifier, 
            $this->notifier->add($notifier)
        );
        
        $this->assertAttributeEquals(
            array(
                spl_object_hash($notifier) => $notifier
            ),
            'notifiers',
            $this->notifier
        );
    }
    
    public function testItRemovesNotifier()
    {
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $notifierSecond = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $this->notifier->add($notifier);
        $this->notifier->add($notifierSecond);
        $this->assertSame($this->notifier, $this->notifier->remove($notifier));
        $this->assertAttributeEquals(
            array(
                spl_object_hash($notifierSecond) => $notifierSecond
            ),
            'notifiers',
            $this->notifier
        );
    }
    
    public function testItReturnsTrueIfObjectDoesNotHaveAnyNotifiers()
    {
        $this->assertTrue($this->notifier->isEmpty());
    }

    public function testItReturnsFalseIfObjectHasAssociatedItem()
    {
        $this->notifier->add(
            $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface')
        );
        $this->assertFalse($this->notifier->isEmpty());
    }
    
    public function testItCallsNotifyMethodOfAddedNotifiers()
    {
        $notifierOne = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $notifierTwo = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');

        $object = new stdClass();
        
        $notifierOne->expects($this->once())
            ->method('notify')
            ->with($object, 'event_code', array('data1' => true));

        $notifierTwo->expects($this->once())
            ->method('notify')
            ->with($object, 'event_code', array('data1' => true));
        
        $this->notifier->add(
            $notifierOne
        );
        
        $this->notifier->add(
            $notifierTwo
        );
        
        $this->notifier->notify(
            $object, 'event_code', array('data1' => true)
        );
    }
    
    public function testItReturnsNotifiers()
    {
        $notifierOne = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $notifierTwo = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');

        $this->notifier->add($notifierOne);
        $this->notifier->add($notifierTwo);
        
        $this->assertEquals(
            array($notifierOne, $notifierTwo),
            $this->notifier->items()
        );
    }
}
