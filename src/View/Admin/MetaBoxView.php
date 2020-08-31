<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\View\View;

/**
 * Class MetaBoxView
 *
 * View for meta boxes.
 *
 * @package Naran\Axis\View\Admin
 */
abstract class MetaBoxView extends View
{
    /**
     * Return metabox id.
     *
     * @return string
     */
    abstract public static function getId();

    /**
     * Return metabox title.
     *
     * @return string
     */
    abstract public static function getTitle();

    /**
     * Render meta box.
     *
     * @param \WP_Post $post
     *
     * @return mixed
     */
    abstract public function renderMetabox($post);

    /**
     * Return draw callback method.
     *
     * @return callable
     */
    public function getCallback()
    {
        return [$this, 'renderMetabox'];
    }

    /**
     * Return screen.
     *
     * @return null|\WP_Screen
     */
    public static function getScreen()
    {
        return null;
    }

    /**
     * Screen context
     *
     * 'normal', 'side', and 'advanced'.
     *
     * @return string
     */
    public static function getContext()
    {
        return 'advanced';
    }

    /**
     * Output priority.
     *
     * 'high', 'default', and 'low'.
     *
     * @return string
     */
    public static function getPriority()
    {
        return 'default';
    }

    /**
     * Return callback function arguments
     *
     * @return array|null
     */
    public static function getCallbackArgs()
    {
        return null;
    }

    /**
     * Add meta box.
     */
    public function addMetaBox()
    {
        add_meta_box(
            static::getId(),
            static::getTitle(),
            $this->getCallback(),
            static::getScreen(),
            static::getContext(),
            static::getPriority(),
            static::getCallbackArgs()
        );
    }
}