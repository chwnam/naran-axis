<?php


namespace Naran\Axis\View;


use function Naran\Axis\Func\toCamelCase;


/**
 * Class Context
 *
 * Class for calculating view context variables.
 *
 * @package Naran\Axis\View
 */
abstract class Context
{
    /**
     * Get context
     *
     * @param array $input
     * @param array $commonParams
     *
     * @return array
     */
    public function getContext($input = [], $commonParams = [])
    {
        $context = [];

        foreach ($input as $variable => $params) {
            $variable = str_replace('-', '_', sanitize_key($variable));
            $params   = array_filter((array)$params);

            if ($variable) {
                $method = [$this, toCamelCase($variable)];
                if (is_callable($method)) {
                    $context[$variable] = call_user_func_array($method, array_merge($commonParams, $params));
                }
            }
        }

        return $context;
    }
}
