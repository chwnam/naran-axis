<?php


namespace Naran\Axis\Model\ValueType;


use BadMethodCallException;
use Naran\Axis\Model\Field\VerificationFailedException;

abstract class ValueType
{
    protected $args = [];

    public function __construct($args = [])
    {
        $this->args = wp_parse_args($args, static::getDefaultArgs());
    }

    public function __get($property)
    {
        if (array_key_exists($property, $this->args)) {
            return $this->args[$property];
        }

        throw new BadMethodCallException();
    }

    public static function getDefaultArgs()
    {
        return [];
    }

    /**
     * Convert value before storing it into db.
     *
     * @param $value
     *
     * @return mixed
     */
    public function export($value)
    {
        return $value;
    }

    /**
     * Convert value from db raw value.
     *
     * @param $value
     *
     * @return mixed
     */
    public function import($value)
    {
        return $value;
    }

    /**
     * Sanitize input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    abstract public function sanitize($value);

    /**
     * Verify sanitized value.
     *
     * @param mixed  $value
     * @param string $label
     *
     * @return mixed
     * @throws VerificationFailedException
     */
    abstract public function verify($value, $label);
}