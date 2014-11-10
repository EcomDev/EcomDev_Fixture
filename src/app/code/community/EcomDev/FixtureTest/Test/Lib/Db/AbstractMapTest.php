<?php

class EcomDev_FixtureTest_Test_Lib_Db_AbstractMapTest 
    extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * @var EcomDev_Fixture_Db_AbstractMap
     */
    protected $map;

    protected function setUp()
    {
        $this->map = $this->getMockForAbstractClass(
            'EcomDev_Fixture_Db_AbstractMap',
            array('test_table_name')
        );
    }

    public function testItHasRequiredAttributes()
    {
        $this->assertObjectHasAttribute('value', $this->map);
        $this->assertObjectHasAttribute('table', $this->map);
    }
    
    public function testItSetsTableDuringInstantiation()
    {
        $this->assertAttributeEquals('test_table_name', 'table', $this->map);
    }

    public function testItIsPossibleToSetIdentifier()
    {
        $this->assertAttributeSame(null, 'value', $this->map);
        $this->map->setValue('12345');
        $this->assertAttributeSame('12345', 'value', $this->map);
    }

    public function testItReturnCorrectIdValue()
    {
        $this->map->setValue('12345');
        $this->assertSame('12345', $this->map->getValue());
    }

    public function testItIsResolvedOnlyIfIdIsSet()
    {
        $this->assertFalse($this->map->isResolved());
        // When value is an identifier
        $this->map->setValue('12345');
        $this->assertTrue($this->map->isResolved());
        // When value is set back to null
        $this->map->setValue(null);
        $this->assertFalse($this->map->isResolved());
    }

    public function testItReturnsIdWhenCastedToString()
    {
        $this->map->setValue('12345');
        $this->assertEquals('12345', (string)$this->map);
    }

    public function testItReturnsCorrectlyTableName()
    {
        $this->assertEquals('test_table_name', $this->map->getTable());
    }

    public function testItImplementsNotifierAwareInterface()
    {
        $this->assertInstanceOf(
            'EcomDev_Fixture_Contract_Utility_NotifierAwareInterface', 
            $this->map
        );

        $this->assertObjectHasAttribute('notifiers', $this->map);
        $this->assertAttributeSame(null, 'notifiers', $this->map);
    }
    
    public function testItIsPossibleToSetNotifierContainer()
    {
        $notifierContainer = new EcomDev_Fixture_Utility_Notifier_Container();
        $this->map->setNotifierContainer($notifierContainer);
        $this->assertAttributeSame($notifierContainer, 'notifiers', $this->map);
    }
    
    public function testItIsReturnAnEmptyArrayIfNotifierIsNotInitialized()
    {
        $this->assertSame(array(), $this->map->getNotifiers());
    }
    
    public function testItInstantiatesNotifierContainerOnFirstNotificationCall()
    {
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $this->assertAttributeSame(null, 'notifiers', $this->map);
        $this->map->addNotifier($notifier);
        $this->assertAttributeInstanceOf('EcomDev_Fixture_Utility_Notifier_Container', 'notifiers', $this->map);
        $this->assertSame(
            array($notifier),
            $this->map->getNotifiers()
        );
    }
    
    public function testItDoesNotFailToRemoveNotifierWhenNoNotifiersAdded()
    {
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $this->assertSame($this->map, $this->map->removeNotifier($notifier));
    }
    
    public function testItCallsAddNotifierMethodOnContainer()
    {
        $containerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        $this->map->setNotifierContainer($containerMock);
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $containerMock->expects($this->once())
            ->method('add')
            ->with($notifier)
            ->willReturnSelf();
        
        $this->assertSame($this->map, $this->map->addNotifier($notifier));
    }
    
    public function testItCallsRemoveNotifierMethodOnContainer()
    {
        $containerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        $this->map->setNotifierContainer($containerMock);
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $containerMock->expects($this->once())
            ->method('remove')
            ->with($notifier)
            ->id('remove_notifier')
            ->willReturnSelf();

        $containerMock->expects($this->once())
            ->method('isEmpty')
            ->after('remove_notifier')
            ->willReturn(false);
        
        $this->assertSame($this->map, $this->map->removeNotifier($notifier));
    }

    public function testItCallsClearsNotifierContainerWhenRemovingLastNotifier()
    {
        $containerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        $this->map->setNotifierContainer($containerMock);
        $notifier = $this->getMockForAbstractClass('EcomDev_Fixture_Contract_Utility_NotifierInterface');
        $containerMock->expects($this->once())
            ->method('remove')
            ->with($notifier)
            ->id('remove_notifier')
            ->willReturnSelf();

        $containerMock->expects($this->once())
            ->method('isEmpty')
            ->after('remove_notifier')
            ->willReturn(true);

        $this->assertSame($this->map, $this->map->removeNotifier($notifier));
        $this->assertAttributeSame(null, 'notifiers', $this->map);
    }
    
    public function testItNotifiesAboutResolveOperationOnSetValue()
    {
        $containerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        $containerMock->expects($this->exactly(2))
            ->method('notify')
            ->withConsecutive(
                array($this->map, 'resolve', true),
                array($this->map, 'resolve', false)
            );
        
        $this->map->setNotifierContainer($containerMock);
        $this->map->setValue(1);
        $this->map->setValue(null);
    }

    public function testItIsPossibleToOverrideTable()
    {
        $this->map->setTable('table_name');
        $this->assertEquals('table_name', $this->map->getTable());
    }
    
    public function testItImplementsResetAwareInterface()
    {
        $this->assertInstanceOf('EcomDev_Fixture_Contract_Utility_ResetAwareInterface', $this->map);
    }
    
    public function testItNotifiesNotifiersOnReset()
    {
        $containerMock = $this->getMock('EcomDev_Fixture_Utility_Notifier_Container');
        $containerMock->expects($this->once())
            ->method('notify')
            ->withConsecutive(
                array($this->map, 'reset')
            );

        $this->map->setNotifierContainer($containerMock);
        $this->assertSame($this->map, $this->map->reset());
    }
}
