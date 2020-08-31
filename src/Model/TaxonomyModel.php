<?php


namespace Naran\Axis\Model;


use Naran\Axis\Model\Holder\MetaHolder;

abstract class TaxonomyModel extends MetaHolder
{
    /**
     * Return taxonomy string.
     *
     * @return string
     */
    abstract public static function getTaxonomy();

    /**
     * Return register_taxonomy() 2nd parameter.
     *
     * @return array|string
     */
    abstract public static function getObjectTypes();

    /**
     * Return register_taxonomy() 3rd parameter.
     *
     * @return array
     */
    abstract public function getArgs();

    /**
     * Alias of getTaxonomy()
     *
     * @return string
     */
    public static function Tax()
    {
        return static::getTaxonomy();
    }

    public function registerTaxonomy()
    {
        if (taxonomy_exists(static::getTaxonomy())) {
            return get_taxonomy(static::getTaxonomy());
        } else {
            $object = register_taxonomy(static::getTaxonomy(), static::getObjectTypes(), $this->getArgs());

            if (is_wp_error($object)) {
                wp_die($object);
            }

            return $object;
        }
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

        $params['object_type']    = 'term';
        $params['object_subtype'] = '';
        $params['taxonomy']       = static::getTaxonomy();

        return $params;
    }
}