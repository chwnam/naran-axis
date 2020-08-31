<?php

namespace Naran\Axis\Model\Holder;

use Naran\Axis\Model\Field\Option;
use Naran\Axis\Model\Model;
use Naran\Axis\View\Admin\FieldWidgets\FieldWidget;


abstract class OptionHolder extends Model
{
    use HolderTrait;

    /**
     * Register all option fields.
     */
    public function registerFields()
    {
        foreach ($this->getAllFields() as $field) {
            $key = $field->key;

            /** @var Option $field */
            if ( ! $field->autoload) {
                add_action("update_option_{$key}", [$this, 'correctAutoload'], 1, 3);
            }

            if ($field->contextual) {
                add_filter("pre_update_option_{$key}", [$this, 'correctContextual'], 1, 3);
            }

            $field->register();
        }
    }

    /**
     * Autoload fix.
     *
     * @callback
     * @action      update_option_{$option}
     *
     * @param        $_
     * @param        $__
     * @param string $option
     *
     * @noinspection PhpUnusedParameterInspection*/
    public function correctAutoload($_, $__, $option)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->options,
            ['autoload' => 'no'],
            ['option_name' => $option]
        );
    }

    /**
     * Contextual correction.
     *
     * @param mixed  $value
     * @param mixed  $current
     * @param string $option
     *
     * @return array
     *
     * @see FieldWidget::renderContext() context reference.
     */
    public function correctContextual($value, $current, $option)
    {
        /** @var Option $field */
        $field    = $this->resolve("option.{$option}");
        $contexts = (array)($_REQUEST['axis_field_widget_context'] ?? []);
        $context  = $contexts[$field->key] ?? null;

        if ($field && $context) {
            $output           = (array)$current;
            $output[$context] = $value;

            return $output;
        }

        return $value;
    }

    /**
     * @param string   $key
     * @param callable $argFunc
     *
     * @return Option
     */
    protected function claimField($key, $argFunc)
    {
        $container = $this->getContainer();
        $abstract  = "option.{$key}";

        if ( ! $container->bound($abstract)) {
            $container->bind(
                $abstract,
                function ($app) use ($key, $argFunc) {
                    return new Option($app->make('starter'), $this->asParamArray($key, $argFunc));
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
