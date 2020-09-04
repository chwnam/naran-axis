<?php

use Naran\Axis\Container\Container;
use Naran\Illuminate\Contracts\Container\BindingResolutionException;
use Naran\Axis\Starter\Starter;
use Naran\Axis\Starter\StarterFailureException;
use Naran\Axis\Starter\StarterPool;


/**
 * Start axis based plugin.
 *
 * @param array $args
 */
function axisStart($args = [])
{
    try {
        Starter::factory($args)->start();
    } catch (StarterFailureException $e) {
        wp_die(esc_html($e->getMessage()), __('Axis startup failed', 'naran-axis'));
    } catch (BindingResolutionException $e) {
        wp_die(esc_html($e->getMessage()), __('Axis startup failed', 'naran-axis'));
    }
}


/**
 * Get starter.
 *
 * @param $slug
 *
 * @return Starter|null
 */
function axisGetStarter($slug)
{
    return StarterPool::getInstance()->getStarter($slug);
}


/**
 * Get container of plugin.
 *
 * @param $slug
 *
 * @return Container|null
 */
function axisGetContainer($slug)
{
    $starter = AxisGetStarter($slug);

    return $starter ? $starter->getContainer() : null;
}


/**
 * Resolve any instance of plugin.
 *
 * @param string $slug
 * @param string $abstract
 * @param array  $parameters
 *
 * @return mixed|null
 */
function axisResolve($slug, $abstract, $parameters = [])
{
    try {
        $container = axisGetContainer($slug);

        return $container ? $container->make($abstract, $parameters) : null;
    } catch (BindingResolutionException $e) {
        return null;
    }
}
