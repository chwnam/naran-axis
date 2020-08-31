<?php


namespace Naran\Axis\Starter\ClassResolver;


use Naran\Axis\Starter\Starter;

class AllGrantedRegionFilter implements RegionFilter
{
    public function filter(string $region, Starter $starter)
    {
        return true;
    }
}
