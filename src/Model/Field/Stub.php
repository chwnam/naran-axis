<?php


namespace Naran\Axis\Model\Field;


use Naran\Axis\Model\ValueType\DummyType;
use Naran\Axis\Starter\Starter;

class Stub extends Field
{
    private $value;

    public function __construct(Starter $starter, array $args = [])
    {
        if ( ! $this->value_type) {
            $args['value_type'] = $this->resolve(DummyType::class);
        }

        parent::__construct($starter, $args);

        $this->value = $this->default;
    }

    public function get()
    {
        return $this->value;
    }

    public function update($value)
    {
        $this->value = $value;

        return true;
    }

    public function delete()
    {
        $this->value = null;

        return true;
    }

    public function getContainerId()
    {
        return "stub.{$this->key}";
    }

    public function register()
    {
    }
}
