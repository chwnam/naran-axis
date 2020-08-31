<?php


namespace Naran\Axis\Model\Holder;

use Naran\Axis\Model\Field\Field;
use Naran\Axis\Starter\Starter;

use function Naran\Axis\Func\strStartsWith;
use function Naran\Axis\Func\toSnakeCase;

/**
 * class Holder
 *
 * @package Naran\Axis\Model\Holder
 *
 * @method Starter getStarter()
 */
trait HolderTrait
{
    protected static $fields = null;

    /**
     * Grab and return all fields.
     *
     * @return Field[]
     */
    public function getAllFields()
    {
        if (is_null(static::$fields)) {
            static::$fields = [];

            foreach (get_class_methods($this) as $method) {
                if (strStartsWith($method, 'getField') && strlen($method) > 8) {
                    /** @var Field $field */
                    $field = call_user_func([$this, $method]);

                    static::$fields[$field->getContainerId()] = $field;
                }
            }
        }

        return static::$fields;
    }

    protected function guessKey($method)
    {
        $name = trim($method, '\\/');

        $pos = strrpos($method, '::');
        if (false !== $pos) {
            $name = substr($method, $pos + 2);
        }

        if (strlen($name) > 8 && substr($name, 0, 8) === 'getField') {
            return $this->getStarter()->prefixed(toSnakeCase(substr($name, 8)));
        } else {
            return $method;
        }
    }
}
