<?php

namespace Naran\Axis\Initiator;

use Naran\Axis\Object\Component;
use Naran\Axis\Starter\Starter;

/**
 * Interface Initiator
 *
 * Initiator: component for organizing WordPress action, filter, and shortcode callbacks.
 *
 * @package Naran\Axis\Initiator
 */
interface Initiator extends Component
{
    /**
     * Define all action, filter calls here.
     *
     * @return void
     */
    public function initHooks();

    /**
     * @return Starter
     */
    public function getStarter();
}
