<?php


namespace Naran\Axis\Model\Holder;


use Naran\Axis\Model\Field\Meta;
use Naran\Axis\Model\Model;

abstract class MetaHolder extends Model
{
    use HolderTrait;

    /**
     * Object type. 'comment', 'post', 'term', and 'user'.
     *
     * To hold other meta object type, do not forget to change this value.
     *
     * @var string
     */
    protected $objectType = 'post';

    /**
     * Register all meta fields.
     */
    public function registerFields()
    {
        foreach ($this->getAllFields() as $field) {
            /** @var Meta $field */
            $field->register();
        }
    }

    /**
     * @param string   $key
     * @param callable $argFunc
     *
     * @return Meta
     */
    protected function claimField($key, $argFunc)
    {
        $container = $this->getContainer();
        $abstract  = "{$this->objectType}_meta.{$key}";

        if ( ! $container->bound($abstract)) {
            $container->bind(
                $abstract,
                function ($app) use ($key, $argFunc) {
                    return new Meta($app->make('starter'), $this->asParamArray($key, $argFunc));
                },
                true
            );
        }

        return $container->get($abstract);
    }

    protected function asParamArray(string $key, callable $argFunc)
    {
        $params = call_user_func($argFunc, $key, $this);

        $params['key'] = $key;

        return $params;
    }
}
