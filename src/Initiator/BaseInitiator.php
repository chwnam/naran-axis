<?php


namespace Naran\Axis\Initiator;

use Naran\Axis\Starter\Starter;

abstract class BaseInitiator implements Initiator
{
    private $starter;

    public function __construct(Starter $starter)
    {
        $this->starter = $starter;
    }

    public function getStarter()
    {
        return $this->starter;
    }
}
