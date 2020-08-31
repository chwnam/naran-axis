<?php


namespace Naran\Axis\Model\ValueType;

/**
 * Class DummyType
 *
 * @package Naran\Axis\Model\ValueType
 */
class DummyType extends ValueType
{
    public function sanitize($value)
    {
        return $value;
    }

    /**
     * @param mixed  $value
     * @param string $label
     *
     * @return mixed
     */
    public function verify($value, $label)
    {
        return $value;
    }
}