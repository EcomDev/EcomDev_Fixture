<?php

use EcomDev_Utils_Reflection as ReflectionUtil;

abstract class EcomDev_FixtureTest_Test_AbstractTestCase
    extends PHPUnit_Framework_TestCase
{
    /**
     * Returns some test data stub
     *
     * @param string $name
     * @return array
     */
    protected function loadDataFile($name)
    {
        $classReflection = ReflectionUtil::getReflection($this);
        $file = $classReflection->getFileName();
        $dataFile =  dirname($file) . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . basename($file, '.php')
            . DIRECTORY_SEPARATOR . $name . '.json';

        return json_decode(file_get_contents($dataFile), true);
    }
    
}
