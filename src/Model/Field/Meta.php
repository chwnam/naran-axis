<?php


namespace Naran\Axis\Model\Field;


use Naran\Axis\Starter\Starter;
use WP_Comment;
use WP_Comment_Query;
use WP_Post;
use WP_Query;
use WP_Term;
use WP_Term_Query;
use WP_User;
use WP_User_Query;


/**
 * Class Meta
 *
 * @package Naran\Axis\Model\Field
 *
 * @property-read bool          $single
 * @property-read string        $object_type
 * @property-read string        $object_subtype
 * @property-read callable|null $auth_callback
 * @property-read bool          $unique
 * @property-read bool          $ordered
 * @property-read string        $taxonomy
 * @property-read string        $key_not_found
 */
class Meta extends Field
{
    /**
     * Meta constructor.
     *
     * @param Starter $starter
     * @param array   $args
     */
    public function __construct(Starter $starter, array $args = [])
    {
        parent::__construct($starter, $args);

        if (is_callable($this->before_add) && ! has_action("add_{$this->object_type}_meta", $this->before_add)) {
            add_action(
                "add_{$this->object_type}_meta",
                $this->before_add,
                $this->getStarter()->getDefaultPriority(),
                3, // $object_id, $meta_key, $meta_value
            );
        }

        if (is_callable($this->after_add) && ! has_action("add_{$this->object_type}_meta", $this->after_add)) {
            add_action(
                "add_{$this->object_type}_meta",
                $this->after_add,
                $this->getStarter()->getDefaultPriority(),
                4, // $mid, $object_id, $meta_key, $_meta_value
            );
        }

        if (
            is_callable($this->before_delete) &&
            ! has_action("delete_{$this->object_type}_meta", $this->after_delete)
        ) {
            add_action(
                "delete_{$this->object_type}_meta",
                $this->after_delete,
                $this->getStarter()->getDefaultPriority(),
                4 // $meta_ids, $object_id, $meta_key, $_meta_value
            );
        }

        if (
            is_callable($this->after_delete) &&
            ! has_action("delete_{$this->object_type}_meta", $this->after_delete)
        ) {
            add_action(
                "delete_{$this->object_type}_meta",
                $this->after_delete,
                $this->getStarter()->getDefaultPriority(),
                4 // $meta_ids, $object_id, $meta_key, $_meta_value
            );
        }

        if (
            is_callable($this->before_update) &&
            ! has_action("update_{$this->object_type}_meta", $this->before_update)
        ) {
            add_action(
                "update_{$this->object_type}_meta",
                $this->before_update,
                $this->getStarter()->getDefaultPriority(),
                4 //  $meta_id, $object_id, $meta_key, $_meta_value
            );
        }

        if (
            is_callable($this->after_update) &&
            ! has_action("updated_{$this->object_type}_meta", $this->after_update)
        ) {
            add_action(
                "updated_{$this->object_type}_meta",
                $this->after_update,
                $this->getStarter()->getDefaultPriority(),
                4 // $meta_id, $object_id, $meta_key, $_meta_value
            );
        }

        if ( ! is_callable($this->args['sanitize_callback'])) {
            $this->args['sanitize_callback'] = [$this, 'defaultSanitizer'];
        }
    }

    public static function getDefaultArgs()
    {
        return array_merge(
            parent::getDefaultArgs(),
            [
                'single'         => true,
                'object_type'    => 'post',
                'object_subtype' => '',
                'auth_callback'  => null,
                'unique'         => false,
                'ordered'        => false,
                'taxonomy'       => '',
                'key_not_found'  => '',
            ]
        );
    }

    public function get($objectId)
    {
        $key    = $this->key;
        $id     = $this->safeObjectId($objectId);
        $single = $this->single;

        /**
         * @see get_comment_meta()
         * @see get_post_meta()
         * @see get_term_meta()
         * @see get_user_meta()
         */
        $value = call_user_func_array("get_{$this->object_type}_meta", [$id, $key, $this->single]);

        if ($single) {
            $value = $this->import($value);
        } else {
            foreach ($value as $i => $v) {
                $value[$i] = $this->import($v);
            }
        }

        if ($this->update_cache && ! is_scalar($value) && ! ($value instanceof $this->type)) {
            $this->updateCache($id, $value);
        }

        return $value;
    }

    public function update($objectId, $value, $prev = '')
    {
        $key        = $this->key;
        $id         = $this->safeObjectId($objectId);
        $objectType = $this->object_type;

        if ($this->unique && ! $this->checkUnique($id, $value)) {
            wp_die(
                __('\'%s\' is a unique field. The value is duplicated.', 'naran-axis'),
                __('Validation Error', 'naran-axis'),
                'response=400&back_link=1'
            );
        }

        if ($this->single || $prev) {
            // single: just update the record.
            // non-single, but prev exist: update record whose value is prev.
            return call_user_func_array("update_{$this->object_type}_meta", [$objectId, $key, $value, $prev]);
        } else {
            $return = true;

            // non-single: The value is considered an array. Replaces the old record.
            //             Each element has to be exported.
            delete_metadata($objectType, $id, $key);

            foreach ((array)$value as $item) {
                $return &= add_metadata($objectType, $id, $key, $item);
            }

            return $return;
        }
    }

    public function delete($objectId, $value = '')
    {
        /**
         * @see delete_comment_meta()
         * @see delete_post_meta()
         * @see delete_term_meta()
         * @see delete_user_meta()
         */
        return call_user_func_array("delete_{$this->object_type}_metadata", [$objectId, $this->key, $value]);
    }

    public function register()
    {
        global $wp_meta_keys;

        $type    = $this->object_type;
        $subtype = $this->object_subtype;
        $key     = $this->key;

        if ( ! isset($wp_meta_keys[$type][$subtype][$key])) {
            register_meta($type, $key, $this->args);
            $this->args = &$wp_meta_keys[$type][$subtype][$key]; // Share args array with core.
        }
    }

    public function defaultSanitizer($value)
    {
        try {
            $sanitized = $this->sanitize($value);
            $verified  = $this->verify($sanitized);

            return $this->export($verified);
        } catch (VerificationFailedException $e) {
            add_settings_error(
                "{$this->object_type}-{$this->object_subtype}",
                "error-{$this->key}",
                sprintf(
                    __('The value for \'%s\' is invalid and replaced with the default value.', 'naran-axis'),
                    $this->label
                ),
                'notice-warning'
            );

            return $this->default;
        }
    }

    protected function updateCache($id, $value)
    {
        $key   = $this->key;
        $group = "{$this->object_type}_meta";
        $cache = wp_cache_get($id, $group);

        if (false === $cache) {
            $cache = [];
        }

        if ($this->single) {
            $cache[$key][0] = $value;
            wp_cache_set($id, $cache, $group);
        } else {
            foreach (array_values((array)$value) as $i => $v) {
                $cache[$key][$i] = $v;
            }
            wp_cache_set($id, $cache, $group);
        }
    }

    protected function safeObjectId($id)
    {
        if (is_int($id) || is_numeric($id)) {
            return intval($id);
        } elseif ($this->object_type === 'comment' && $id instanceof WP_Comment) {
            return $id->comment_ID;
        } elseif ($this->object_type === 'post' && $id instanceof WP_Post) {
            return $id->ID;
        } elseif ($this->object_type === 'term' && $id instanceof WP_Term) {
            return $id->term_id;
        } elseif ($this->object_type === 'user' && $id instanceof WP_User) {
            return $id->ID;
        }

        return false;
    }

    protected function checkUnique($id, $value)
    {
        switch ($this->object_type) {
            case 'comment':
                return $this->checkUniqueComment($id, $value);

            case 'post':
                return $this->checkUniquePost($id, $value);

            case 'term':
                return $this->checkUniqueTerm($id, $value);

            case 'user':
                return $this->checkUniqueUser($id, $value);
        }

        return false;
    }

    protected function checkUniqueComment($id, $value)
    {
        $comment = get_comment($id);
        if ( ! $comment) {
            return false;
        }

        $post = get_post($comment->comment_post_ID);
        if ( ! $post) {
            return false;
        }

        $query = new WP_Comment_Query(
            [
                'comment__not_in' => $id,
                'post_type'       => $post->post_type,
                'meta_key'        => $this->key,
                'meta_value'      => $this->export($value),
                'number'          => 1,
                'type'            => 'comment',
                'count'           => true,
            ]
        );

        return $query->get_comments() == 0;
    }

    protected function checkUniquePost($id, $value)
    {
        $post = get_post($id);
        if ( ! $post) {
            return false;
        }

        $query = new WP_Query(
            [
                'post__not_in'     => [$post->ID],
                'post_type'        => $post->post_type,
                'post_status'      => ['publish', 'pending', 'draft', 'future', 'private'],
                'meta_key'         => $this->key,
                'meta_value'       => $this->export($value),
                'fields'           => 'ids',
                'posts_per_page'   => 1,
                'no_found_rows'    => true,
                'suppress_filters' => true,
            ]
        );

        return $query->post_count == 0;
    }

    protected function checkUniqueTerm($id, $value)
    {
        $term = get_term($id);
        if ( ! $term) {
            return false;
        }

        $query = new WP_Term_Query(
            [
                'exclude'          => [$term->term_id],
                'taxonomy'         => $this->taxonomy,
                'meta_key'         => $this->key,
                'meta_value'       => $this->export($value),
                'hide_empty'       => false,
                'suppress_filters' => true,
                'number'           => 1,
                'fields'           => 'ids',
            ]
        );

        return sizeof($query->get_terms()) == 0;
    }

    protected function checkUniqueUser($id, $value)
    {
        $user = get_user_by('id', $id);
        if ( ! $user) {
            return $user;
        }

        $query = new WP_User_Query(
            [
                'exclude'          => [$user->ID],
                'meta_key'         => $this->key,
                'meta_value'       => $this->export($value),
                'suppress_filters' => true,
                'count_total'      => false,
                'number'           => 1,
                'search_columns'   => ['ID'],
            ]
        );

        return sizeof($query->get_results()) == 0;
    }

    public function getContainerId()
    {
        return "{$this->object_type}_meta.{$this->key}";
    }
}
