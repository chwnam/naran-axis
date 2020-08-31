<?php


namespace Naran\Axis\Model;


use Naran\Axis\Model\Field\Option;
use Naran\Axis\Model\Holder\OptionHolder;

abstract class BaseSettingsModel extends OptionHolder implements ActivationDeactivation
{
    abstract public static function getOptionGroup();

    /**
     * Correct autoload='yes' values event if declared as autoload='no'.
     */
    public function activationSetup()
    {
        $options = array_map(
            function ($field) {
                /** @var Option $field */
                return $field->key;
            },
            array_filter(
                $this->getAllFields(),
                function ($field) {
                    /** @var Option $field */
                    return ! $field->autoload;
                }
            )
        );

        if ($options) {
            $wpdb = static::wpdb();
            $pad  = implode(', ', array_pad([], count($options), '%s'));
            $wpdb->query(
                $wpdb->prepare(
                    "# noinspection SqlResolve
UPDATE `{$wpdb->options}` SET `autoload`='no' WHERE `option_name` IN ({$pad}) AND `autoload`='yes'",
                    $options
                )
            );
        }
    }

    public function deactivationCleanup()
    {
    }

    /**
     * @override
     *
     * @param string   $key
     * @param callable $argFunc
     *
     * @return array
     */
    protected function asParamArray(string $key, callable $argFunc)
    {
        $params = parent::asParamArray($key, $argFunc);

        if (empty($params['group'])) {
            $params['group'] = static::getOptionGroup();
        }

        return $params;
    }
}
