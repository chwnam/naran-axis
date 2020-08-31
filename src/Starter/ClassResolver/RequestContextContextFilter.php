<?php


namespace Naran\Axis\Starter\ClassResolver;


use Naran\Axis\Starter\Starter;

use function Naran\Axis\Func\isRequest;

class RequestContextContextFilter implements ContextFilter
{
    public function filter(string $context, Starter $starter)
    {
        return isRequest($context);
    }
}
