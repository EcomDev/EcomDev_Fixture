<?php

use EcomDev_Fixture_Utility_HashMap as HashMap;

class EcomDev_FixtureTest_Test_Lib_Utility_HashMapTest extends EcomDev_FixtureTest_Test_AbstractTestCase
{
    /**
     * @var HashMap
     */
    private $hashMap;
    
    protected function setUp()
    {
        $this->hashMap = new HashMap();
    }
    
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A hash map key cannot be null
     */
    public function testItRisesAnExceptionIfKeyIsNull()
    {
        $this->hashMap[] = array();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A hash map key does not exists
     */
    public function testItRisesExceptionIfItemWithThisKeyDoesNotExists()
    {
        $this->hashMap[array('item2')];
    }
    
    public function testItAllowsArrayAsAKey()
    {
        $key = array(1);
        $this->hashMap[$key] = array(2);
        $this->assertSame(array(2), $this->hashMap[$key]);
    }

    public function testItAllowsEmptyArrayAsAKey()
    {
        $key = array();
        $this->hashMap[$key] = array(2);
        $this->assertSame(array(2), $this->hashMap[$key]);
    }
    
    public function testItOverridesValueForTheSameArrayKey()
    {
        $key = array(1);

        $this->hashMap[$key] = array(2);
        $this->assertSame(array(2), $this->hashMap[$key]);

        $this->hashMap[$key] = array(4);
        $this->assertSame(array(4), $this->hashMap[$key]);
    }
    
    public function testItAllowsUseOfObjectAsAKey()
    {
        $key1 = new stdClass();
        $key2 = new stdClass();
        $this->hashMap[$key1] = 'data1';
        $this->hashMap[$key2] = 'data2';

        $this->assertSame('data1', $this->hashMap[$key1]);
        $this->assertSame('data2', $this->hashMap[$key2]);
    }
    
    public function testItIsPossibleToCheckKeyAvailability()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        $nonSetKey = new stdClass();
        
        $this->hashMap[$key1] = 'value';
        $this->hashMap[$key2] = 'value2';
        $this->assertTrue(isset($this->hashMap[$key1]));
        $this->assertTrue(isset($this->hashMap[$key2]));
        $this->assertFalse(isset($this->hashMap[$nonSetKey]));
    }
    
    public function testItIsPossibleToRemoveItemFromHash()
    {
        $key1 = array(1);
        
        $this->hashMap[$key1] = 'value';
        $this->assertSame('value', $this->hashMap[$key1]);
        unset($this->hashMap[$key1]);
        
        $this->assertFalse(isset($this->hashMap[$key1]));
        $this->setExpectedException('InvalidArgumentException', 'A hash map key does not exists');
        $this->hashMap[$key1];
    }
    
    public function testItIsPossibleToIterateOverHashMapPairs()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        
        $expectedIterationResult = array(
            array($key1, 'value1'),
            array($key2, 'value2')
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        
        $result = array();

        $this->hashMap->each(function () use (&$result) {
            $result[] = func_get_args();
        });
        
        $this->assertSame($expectedIterationResult, $result, 'Iteration');
    }
    
    public function testItIsPossibleToIterateOverHashMapValues()
    {
        $key1 = array(1);
        $key2 = new stdClass();

        $expectedIterationResult = array(
            array('value1'),
            array('value2')
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';

        $result = array();

        $this->hashMap->each(
            function () use (&$result) {
                $result[] = func_get_args();
            },
            HashMap::OPTION_VALUE
        );

        $this->assertSame($expectedIterationResult, $result, 'Iteration');
    }

    public function testItIsPossibleToIterateOverHashMapKeys()
    {
        $key1 = array(1);
        $key2 = new stdClass();

        $expectedIterationResult = array(
            array($key1),
            array($key2)
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';

        $result = array();

        $this->hashMap->each(
            function () use (&$result) {
                $result[] = func_get_args();
            },
            HashMap::OPTION_KEY
        );

        $this->assertSame($expectedIterationResult, $result, 'Iteration');
    }

    public function testItIsPossibleToFilterDataByPairAndReturnIt()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        $key3 = array(2);

        $expectedIterationResult = array(
            array($key1, 'value1'),
            array($key2, 'value2')
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        $this->hashMap[$key3] = 'value3';

        $this->assertSame($expectedIterationResult, $this->hashMap->filter(function ($key, $value)  { 
            return is_array($key) && $value !== 'value3' || $value === 'value2';
        }));
    }

    public function testItIsPossibleToFilterDataByValueAndReturnKey()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        $key3 = array(2);

        $expectedIterationResult = array(
            $key2
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        $this->hashMap[$key3] = 'value3';

        $this->assertSame($expectedIterationResult, $this->hashMap->filter(
            function ($value)  { return $value === 'value2';}, 
            HashMap::OPTION_VALUE, 
            HashMap::OPTION_KEY
        ));
    }

    public function testItIsPossibleToFilterDataByValueAndReturnValue()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        $key3 = array(2);

        $expectedIterationResult = array(
            'value2'
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        $this->hashMap[$key3] = 'value3';

        $this->assertSame($expectedIterationResult, $this->hashMap->filter(
            function ($value)  { return $value === 'value2';},
            HashMap::OPTION_VALUE
        ));
    }
    
    public function testItIsPossibleToFilterDataByKeyAndReturnPair()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        $key3 = array(2);

        $expectedIterationResult = array(
            array($key1, 'value1'),
            array($key3, 'value3')
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        $this->hashMap[$key3] = 'value3';
        
        $this->assertSame($expectedIterationResult, $this->hashMap->filter('is_array', HashMap::OPTION_KEY, HashMap::OPTION_PAIR));
    }

    public function testItIsPossibleToMapData()
    {
        $key1 = array(1);
        $key2 = new stdClass();

        $expectedIterationResult = array(
            array('key' => $key1, 'value' => 'value1'),
            array('key' => $key2, 'value' => 'value2'),
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';

        $this->assertSame($expectedIterationResult, $this->hashMap->map(function ($key, $value) { 
            return array('key' => $key, 'value' => $value); 
        }));
    }

    public function testItIsPossibleToMapDataPairsFilteredByKey()
    {
        $key1 = array(1);
        $key2 = 'test_string_key';
        $key3 = array(2);

        $expectedIterationResult = array(
            array('key' => $key1, 'value' => 'value1'),
            array('key' => $key3, 'value' => 'value3'),
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        $this->hashMap[$key3] = 'value3';

        $mappedData = $this->hashMap
            ->map(
                function ($key, $value) {
                    return array('key' => $key, 'value' => $value);
                },
                HashMap::OPTION_PAIR,
                'is_array',
                HashMap::OPTION_KEY
            );
        
        $this->assertSame($expectedIterationResult, $mappedData);
    }

    public function testItIsPossibleToMapDataPairsFilteredByPair()
    {
        $key1 = array(1);
        $key2 = new stdClass();
        $key3 = array(2);

        $expectedIterationResult = array(
            array('key' => $key1, 'value' => 'value1'),
            array('key' => $key3, 'value' => 'value3'),
        );

        $this->hashMap[$key1] = 'value1';
        $this->hashMap[$key2] = 'value2';
        $this->hashMap[$key3] = 'value3';

        $mappedData = $this->hashMap
            ->map(
                function ($key, $value) {
                    return array('key' => $key, 'value' => $value);
                },
                HashMap::OPTION_PAIR,
                'is_array',
                HashMap::OPTION_KEY
            );

        $this->assertSame($expectedIterationResult, $mappedData);
    }
    
    public function testItIsPossibleToResetHashMap()
    {
        $key = array(1);
        $this->hashMap[$key] = array(2);
        $this->hashMap->reset();
        $this->assertAttributeEmpty('keys', $this->hashMap);
        $this->assertAttributeEmpty('values', $this->hashMap);
    }
}
