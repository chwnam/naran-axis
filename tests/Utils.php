<?php

namespace Naran\Axis\Tests\Func;

use Naran\Axis\Starter\Starter;
use Naran\Axis\Starter\StarterFailureException;

function getStarter()
{
    try {
        return Starter::factory(
            [
                'main_file' => __DIR__ . '/Sample/sample.php',
                'version'   => '1.0.0',
                'src'       => '',
            ]
        );
    } catch (StarterFailureException $e) {
        die($e->getMessage());
    }
}
