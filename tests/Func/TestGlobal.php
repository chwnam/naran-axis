<?php

namespace Naran\Axis\Tests\Func;

use Naran\Axis\Container\Container;
use Naran\Axis\Starter\Starter;
use WP_UnitTestCase;

class TestGlobal extends WP_UnitTestCase
{
    public function testGlobal()
    {
        axisStart(
            [
                'main_file' => dirname(__DIR__) . '/Sample/sample.php',
                'version'   => '1.0.0',
                'slug'      => 'sample',
                'src'       => dirname(AXIS_MAIN) . '/tests/Sample',
                'namespace' => 'Naran\\Axis\\Tests\\Sample\\'
            ]
        );

        $starter = axisGetStarter('sample');

        $this->assertInstanceOf(Starter::class, $starter);

        $container = axisGetContainer('sample');

        $this->assertInstanceOf(Container::class, $container);
    }
}
