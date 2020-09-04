<?php


namespace Naran\Axis\Tests\Starter\ClassFinder;


use Naran\Axis\Starter\ClassFinder\AutoDiscoverClassFinder;
use WP_UnitTestCase;

class TestAutoDiscoverClassFinder extends WP_UnitTestCase
{
    public function testFind()
    {
        $root = dirname(AXIS_MAIN) . '/tests/Sample';

        $finder = new AutoDiscoverClassFinder(
            ['Initiator', 'Model'],
            'Naran\\Axis\\Tests\\Sample\\',
            $root
        );

        $found = $finder->find();

        foreach ($found as $item) {
            $component = $item[1];
            $context   = $item[2];
            $path      = $item[3];
            $fqcn      = $item[4];

            $this->assertFileExists($path);
            $this->assertTrue(class_exists($fqcn));
            $this->assertDirectoryExists("{$root}/{$component}/{$context}");
        }
    }
}