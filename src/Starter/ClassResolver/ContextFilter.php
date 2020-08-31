<?php


namespace Naran\Axis\Starter\ClassResolver;

use Naran\Axis\Starter\Starter;

interface ContextFilter
{
    public function filter(string $context, Starter $starter);
}
