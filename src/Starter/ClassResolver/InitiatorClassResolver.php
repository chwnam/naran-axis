<?php


namespace Naran\Axis\Starter\ClassResolver;

use Naran\Axis\Initiator\Initiator;
use Naran\Axis\Starter\ClassFinder\ClassFinder;
use Naran\Axis\Starter\Starter;

class InitiatorClassResolver implements ClassResolver
{
    private $starter;

    private $finder;

    private $regionFilter;

    private $contextFilter;

    public function __construct(
        Starter $starter,
        ClassFinder $finder,
        RegionFilter $regionFilter,
        ContextFilter $contextFilter
    ) {
        $this->starter       = $starter;
        $this->finder        = $finder;
        $this->regionFilter  = $regionFilter;
        $this->contextFilter = $contextFilter;
    }

    public function resolve()
    {
        $container = $this->starter->getContainer();

        foreach ($this->finder->find() as [$region, $component, $context, $path, $fqcn]) {
            if (
                $this->regionFilter->filter($region, $this->starter) &&
                $this->contextFilter->filter($context, $this->starter) &&
                ($implements = class_implements($fqcn)) && isset($implements[Initiator::class])
            ) {
                $container->singletonIf($fqcn);
                /** @var Initiator $instance */
                $instance = $container->get($fqcn);
                $instance->initHooks();
            }
        }
    }
}
