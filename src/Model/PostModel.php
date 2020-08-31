<?php


namespace Naran\Axis\Model;


use Naran\Axis\Model\Holder\MetaHolder;

/**
 * Class Post
 *
 * Custom post model.
 *
 * @package Naran\Axis\Model
 */
abstract class PostModel extends MetaHolder
{
    /**
     * Return post type string.
     *
     * @return string
     */
    abstract public static function getPostType();

    /**
     * 'register_post_type()' 2nd argument.
     *
     * @return array
     */
    abstract public function getArgs();

    /**
     * Alias of getPostType()
     *
     * @return string
     */
    public static function Pt()
    {
        return static::getPostType();
    }

    public function registerPostType()
    {
        if (post_type_exists(static::getPostType())) {
            $object = get_post_type_object(static::getPostType());
        } else {
            $object = register_post_type(static::getPostType(), $this->getArgs());
            if (is_wp_error($object)) {
                wp_die($object);
            }
        }

        return $object;
    }

    protected function addCaps($roles, $exclude = [])
    {
        $wpRoles = wp_roles();
        $object  = $this->registerPostType();

        $capsExcl = [];
        foreach ($exclude as $e) {
            if (isset($object->cap->{$e})) {
                $capsExcl[] = $object->cap->{$e};
            }
        }

        $caps = array_diff($this->getCapabilities(), $capsExcl);

        foreach ((array)$roles as $role) {
            if (isset($wpRoles->roles[$role])) {
                foreach ($caps as $cap) {
                    $wpRoles->roles[$role]['capabilities'][$cap] = true;
                }
            }
        }

        // NOTE: Each add_cap() calls update. Update all at once.
        update_option($wpRoles->role_key, $wpRoles->roles);
    }

    protected function removeCaps($roles)
    {
        $wpRoles = wp_roles();
        $caps    = $this->getCapabilities();

        foreach ((array)$roles as $role) {
            if (isset($wpRoles->roles[$role])) {
                foreach ($caps as $cap) {
                    unset($wpRoles->roles[$role]['capabilities'][$cap]);
                }
            }
        }

        // NOTE: Each add_cap() calls update. Update all at once.
        update_option($wpRoles->role_key, $wpRoles->roles);
    }

    protected function getCapabilities()
    {
        $object = $this->registerPostType();

        return [
            $object->cap->edit_posts,
            $object->cap->edit_others_posts,
            $object->cap->delete_posts,
            $object->cap->publish_posts,
            $object->cap->read_private_posts,
            $object->cap->delete_private_posts,
            $object->cap->delete_published_posts,
            $object->cap->delete_others_posts,
            $object->cap->edit_private_posts,
            $object->cap->edit_published_posts,
        ];
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

        $params['object_type']    = 'post';
        $params['object_subtype'] = static::getPostType();

        return $params;
    }
}
