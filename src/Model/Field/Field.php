<?php


namespace Naran\Axis\Model\Field;


use BadMethodCallException;
use InvalidArgumentException;
use Naran\Axis\Model\ValueType\ValueType;
use Naran\Axis\Object\Component;
use Naran\Axis\Object\ComponentTrait;
use Naran\Axis\Starter\Starter;

/**
 * Class Field
 *
 * @package Naran\Axis\Model\Field
 *
 * @property-read string           $container_id
 * @property-read string           $key
 * @property-read string           $description
 * @property-read string           $type
 * @property-read ValueType|string $value_type
 * @property-read mixed            $default
 * @property-read string           $label
 * @property-read string           $short_label
 * @property-read bool             $show_in_rest
 * @property-read bool             $required
 * @property-read string           $required_message
 * @property-read callable|null    $before_add
 * @property-read callable|null    $after_add
 * @property-read callable|null    $before_delete
 * @property-read callable|null    $after_delete
 * @property-read callable|null    $before_update
 * @property-read callable|null    $after_update
 * @property-read callable|null    $extra_sanitizer
 * @property-read callable|null    $extra_verifier
 * @property-read callable|null    $sanitize_callback
 * @property-read bool             $update_cache
 */
abstract class Field implements Component
{
    protected $args = [];

    use ComponentTrait;

    public function __construct(Starter $starter, array $args = [])
    {
        $this->starter = $starter;
        $this->args    = wp_parse_args($args, static::getDefaultArgs());

        if ( ! $this->key) {
            throw new InvalidArgumentException(__('Argument \'key\' is required.', 'naran-axis'));
        } elseif ( ! $this->value_type) {
            throw new InvalidArgumentException(__('Argument \'value_type\' is required.', 'naran-axis'));
        }

        if (is_string($this->args['value_type'])) {
            $this->args['value_type'] = $this->resolve($this->args['value_type']);
        }

        $this->args['container_id'] = $this->getContainerId();
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
        return [
            'alias'             => '',
            'key'               => '',
            'description'       => '',
            'type'              => '',
            'value_type'        => null,
            'default'           => '',
            'label'             => '',
            'short_label'       => '',
            'show_in_rest'      => false,
            'required'          => false,
            'before_add'        => null,
            'after_add'         => null,
            'before_delete'     => null,
            'after_delete'      => null,
            'before_update'     => null,
            'after_update'      => null,
            'required_message'  => '',
            'extra_sanitizer'   => null,
            'extra_verifier'    => null,
            'sanitize_callback' => null,
            'update_cache'      => true,
        ];
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value)
    {
        /** @var ValueType $valueType */
        $sanitized = $this->value_type->sanitize($value);

        if (is_callable($this->extra_sanitizer)) {
            $sanitized = call_user_func($this->extra_sanitizer, $sanitized, $value, $this);
        }

        return $sanitized;
    }

    /**
     * @param $value
     *
     * @return mixed
     *
     * @throws VerificationFailedException
     */
    public function verify($value)
    {
        /** @var ValueType $valueType */
        $verified = $this->value_type->verify($value, $this->label);

        if (is_callable($this->extra_verifier)) {
            $verified = call_user_func($this->extra_verifier, $verified, $value, $this);
        }

        return $verified;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function import($value)
    {
        return $this->value_type->import($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function export($value)
    {
        return $this->value_type->export($value);
    }

    abstract public function register();

    /**
     * Generate a unique string across all field variants. e.g. options, and meta fields.
     *
     * @return string
     */
    abstract public function getContainerId();
}
