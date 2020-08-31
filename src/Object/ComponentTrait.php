<?php


namespace Naran\Axis\Object;


use Illuminate\Contracts\Container\BindingResolutionException;
use Naran\Axis\Container\Container;
use Naran\Axis\Starter\Starter;
use Naran\Axis\View\View;

/**
 * Trait ComponentTrait
 *
 * @package Naran\Axis\Object
 *
 * @used-by View
 * @used-by Model
 */
trait ComponentTrait
{
    private $starter;

    /**
     * @return Starter
     */
    public function getStarter()
    {
        return $this->starter;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->getStarter()->getContainer();
    }

    /**
     * Get type from the container.
     *
     * @param string $abstract
     * @param array  $parameters
     *
     * @return mixed|null
     */
    public function resolve($abstract, $parameters = [])
    {
        try {
            return $this->getContainer()->make($abstract, $parameters);
        } catch (BindingResolutionException $e) {
            wp_die($e->getMessage());
        }
    }
}