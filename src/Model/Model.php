<?php


namespace Naran\Axis\Model;


use Naran\Axis\Object\Component;
use Naran\Axis\Object\ComponentTrait;
use Naran\Axis\Starter\Starter;


/**
 * Class Model
 *
 * @package Naran\Axis\Model
 */
class Model implements Component
{
    use ComponentTrait;

    public function __construct(Starter $starter)
    {
        $this->starter = $starter;
    }

    public function db()
    {
        return $this->resolve('db');
    }

    /**
     * @return \wpdb
     */
    public static function wpdb()
    {
        global $wpdb;

        return $wpdb;
    }
}
