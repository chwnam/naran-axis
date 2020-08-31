<?php


namespace Naran\Axis\Model\Field;

use Naran\Axis\Starter\Starter;

/**
 * Class Option
 *
 * @package Naran\Axis\Model\Field
 *
 * @property-read bool   $autoload
 * @property-read bool   $contextual
 * @property-read string $group
 * @property-read bool   $hidden
 */
class Option extends Field
{
    /**
     * Current context. Only for contextual option.
     *
     * @var string
     *
     * @used-by get()
     * @used-by defaultSanitizer()
     */
    protected $context = '';

    /**
     * Option constructor.
     *
     * @param Starter $starter
     * @param array   $args
     */
    public function __construct(Starter $starter, array $args = [])
    {
        parent::__construct($starter, $args);

        if (is_callable($this->before_add) && ! has_action('add_option', $this->before_add)) {
            add_action(
                'add_option',
                $this->before_add,
                $this->getStarter()->getDefaultPriority(),
                2, // $option, value
            );
        }

        if (is_callable($this->after_add) && ! has_action('added_option', $this->after_add)) {
            add_action(
                'added_option',
                $this->after_add,
                $this->getStarter()->getDefaultPriority(),
                2, // $option, value
            );
        }

        if (is_callable($this->before_delete) && ! has_action('delete_option', $this->after_delete)) {
            add_action(
                'delete_option',
                $this->after_delete,
                $this->getStarter()->getDefaultPriority(),
                1 // $option
            );
        }

        if (is_callable($this->after_delete) && ! has_action('deleted_option', $this->after_delete)) {
            add_action(
                'deleted_option',
                $this->after_delete,
                $this->getStarter()->getDefaultPriority(),
                1 // $option
            );
        }

        if (is_callable($this->before_update) && ! has_action('update_option', $this->before_update)) {
            add_action(
                'update_option',
                $this->before_update,
                $this->getStarter()->getDefaultPriority(),
                3 // $option, $old_value, $value
            );
        }

        if (is_callable($this->after_update) && ! has_action('updated_option', $this->after_update)) {
            add_action(
                'updated_option',
                $this->after_update,
                $this->getStarter()->getDefaultPriority(),
                3 // $option, $old_value, $value
            );
        }

        if ( ! $this->args['sanitize_callback']) {
            $this->args['sanitize_callback'] = [$this, 'defaultSanitizer'];
        }
    }

    public static function getDefaultArgs()
    {
        return array_merge(
            parent::getDefaultArgs(),
            [
                'autoload'      => true,
                'contextual'    => false,
                'group'         => '',
                'before_update' => null,
                'after_update'  => null,
                'hidden'        => false,
            ]
        );
    }

    /**
     * Get option.
     *
     * @param null|string $context
     *
     * @return mixed
     */
    public function get($context = null)
    {
        $value   = null;
        $key     = $this->key;
        $default = $this->default;

        if ($this->contextual) {
            $this->context = $context;

            $whole = (array)get_option($key, $default);

            if (array_key_exists($context, $whole)) {
                $value = $whole[$context];
            } elseif (is_array($default) && array_key_exists($context, $default)) {
                $value = $default[$context];
            } else {
                $value = $default;
            }

            $value = $this->import($value);

            if ($this->update_cache && ! is_scalar($value) && ! ($value instanceof $this->type)) {
                $whole[$context] = $value;
                $this->updateCache($whole);
            }

            $this->context = '';
        } else {
            $value = get_option($key, $default);
            $value = $this->import($value);

            if ($this->update_cache && ! is_scalar($value) && ! ($value instanceof $this->type)) {
                $this->updateCache($value);
            }
        }

        return $value;
    }

    /**
     * Update option.
     *
     * @param mixed       $value
     * @param null|string $context
     *
     * @return bool
     */
    public function update($value, $context = null)
    {
        if ($this->contextual) {
            $this->context = $context;
        }

        $return = update_option($this->key, $value, $this->autoload);

        if ($this->contextual) {
            $this->context = '';
        }

        return $return;
    }

    /**
     * Delete option.
     *
     * @return bool
     */
    public function delete()
    {
        return delete_option($this->key);
    }

    /**
     * Register option.
     *
     * @return void
     */
    public function register()
    {
        global $wp_registered_settings;

        $group = $this->group;
        $key   = $this->key;

        if ( ! isset($wp_registered_settings[$key])) {
            register_setting($group, $key, $this->args);
            $this->args = &$wp_registered_settings[$key]; // Share args array with core.
        }
    }

    /**
     * Default option value sanitizer callback function.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function defaultSanitizer($value)
    {
        try {
            $sanitized = $this->sanitize($value);
            $verified  = $this->verify($sanitized);

            if ($this->contextual) {
                // contextual interpolation
                if ( ! is_array($value)) {
                    $value = [];
                }
                $value[$this->context] = $this->export($verified);
            } else {
                $value = $this->export($verified);
            }
        } catch (VerificationFailedException $e) {
            add_settings_error(
                $this->group,
                'warning-' . $this->key,
                sprintf(
                    __('The option value for \'%s\' is invalid and replaced with the the default value.', 'naran-axis'),
                    $this->label
                ),
                'notice-warning'
            );

            $default = $this->default;
            if ($this->contextual) {
                if (is_array($default) && array_key_exists($this->context, $default)) {
                    $value = $default[$this->context];
                } else {
                    $value = $default;
                }
            } else {
                $value = $default;
            }
        }

        return $value;
    }

    /**
     * Update object cache value
     *
     * @param mixed $value
     */
    protected function updateCache($value)
    {
        $key = $this->key;

        // autoload='yes' options are cached in 'alloptions'.
        $autoload = wp_cache_get('alloptions', 'options');

        if (array_key_exists($key, $autoload)) {
            if ($value !== $autoload[$key]) {
                $autoload[$key] = $value;
                wp_cache_replace('alloptions', $autoload, 'options');
            }
        } else {
            // autoload='no' options are separately cached.
            $cached = wp_cache_get($key, 'options');
            if ($value !== $cached) {
                wp_cache_set($key, $value, 'options');
            }
        }
    }

    public function getContainerId()
    {
        return "option.{$this->key}";
    }
}
