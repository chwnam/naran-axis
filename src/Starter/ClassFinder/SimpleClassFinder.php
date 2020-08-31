<?php


namespace Naran\Axis\Starter\ClassFinder;


class SimpleClassFinder implements ClassFinder
{
    private $classes = [];

    public function find()
    {
        return $this->classes;
    }

    public function addClass($context, $class)
    {
        $trim = function ($value) {
            return ltrim($value, '\\');
        };

        if ( ! isset($this->classes[$context])) {
            $this->classes[$context] = [];
        }

        if (is_array($class)) {
            $this->classes[$context] = array_merge($this->classes[$context], array_map($trim, $class));
        } else {
            $this->classes[$context] = $trim($class);
        }
    }
}
