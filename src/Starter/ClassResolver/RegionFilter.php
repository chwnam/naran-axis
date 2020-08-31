<?php


namespace Naran\Axis\Starter\ClassResolver;


use Naran\Axis\Starter\Starter;

interface RegionFilter
{
    public function filter(string $region, Starter $starter);
}
