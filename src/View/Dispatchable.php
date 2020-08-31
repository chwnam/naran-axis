<?php


namespace Naran\Axis\View;


/**
 * Interface Dispatchable
 *
 * Any classes implements this interface mean that calling their dispatch() methods do their own primary objectives.
 *
 * @package Naran\Axis\View
 */
interface Dispatchable
{
    public function dispatch();
}
