<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\Starter\Starter;
use Naran\Axis\View\View;

/**
 * Class MenuPageView
 *
 * @package Naran\Axis\View\Admin
 */
abstract class MenuPageView extends View
{
    /**
     * Page hook string
     *
     * @var string
     */
    protected $hook;

    /**
     * Fix page hook.
     *
     * @var bool
     * @see MenuPageView::alterPageHook()
     */
    protected $fixPageHook = false;

    public function __construct(Starter $starter, bool $fixPageHook = false)
    {
        parent::__construct($starter);

        $this->fixPageHook = $fixPageHook;
    }

    /**
     * Page title (displayed at the browser title bar).
     *
     * @return string
     */
    abstract public static function getPageTitle();

    /**
     * MenuView title (displayed in the WordPress admin menu).
     * @return string
     */
    abstract public static function getMenuTitle();

    /**
     * Capability string.
     *
     * @return string
     */
    abstract public static function getCapability();

    /**
     * MenuView slug, an identifier.
     *
     * @return string
     */
    abstract public static function getMenuSlug();

    /**
     * Default render callback.
     *
     * @return void
     */
    abstract public function dispatch();

    /**
     * Icon URL.
     *
     * @return string
     */
    public static function getIconUrl()
    {
        return '';
    }

    /**
     * MenuView position.
     *
     * @return int|null
     */
    public static function getPosition()
    {
        return null;
    }

    /**
     * Return page hook.
     *
     * @return string
     */
    public function getHook()
    {
        return $this->hook;
    }

    /**
     * Return render callback.
     *
     * @override
     *
     * @return callable
     */
    public function getRenderCallback()
    {
        return [$this, 'dispatch'];
    }

    /**
     * Return load-{$page_hook} callback.
     *
     * @override
     *
     * @return callable|null
     */
    public function getLoadPageHookCallback()
    {
        return null;
    }

    /**
     * Add menu.
     *
     * Call this method in 'admin_menu' action callbacks.
     */
    public function addMenuPage()
    {
        if ($this->fixPageHook) {
            add_filter(
                'sanitize_title',
                [$this, 'alterPageHook'],
                $this->getStarter()->getDefaultPriority(),
                3
            );
        }

        $this->hook = add_menu_page(
            static::getPageTitle(),
            static::getMenuTitle(),
            static::getCapability(),
            static::getMenuSlug(),
            $this->getRenderCallback(),
            static::getIconUrl(),
            static::getPosition()
        );

        if ($this->fixPageHook) {
            remove_filter(
                'sanitize_title',
                [$this, 'alterPageHook'],
                $this->getStarter()->getDefaultPriority()
            );
        }

        $callback = $this->getLoadPageHookCallback();
        $hook     = $this->getHook();
        if ($hook && is_callable($callback)) {
            add_action("load-{$hook}", $callback, $this->getStarter()->getDefaultPriority());
        }
    }

    /**
     * Fix page hook name.
     *
     * You might need this if you have implemented custom table in the custom page.
     * In admin screen, a user can have a table's column to be shown or hide.
     * This action calls wp_ajax_hidden_columns(), and the problem may occur:
     *
     * 1. This AJAX request contains columns id information.
     * 2. Column id may have url-encoded characters, which are converted from their page title.
     * 3. Sanitize function removes these url-encoded characters.
     * 4. In the result, wrong columns information is passed, and all hide/show actions go wring.
     *
     * @callback
     * @filter      sanitize_title
     *
     * @param $title
     * @param $rawTitle
     * @param $context
     *
     * @return string
     *
     * @see         add_menu_page()
     * @see         sanitize_title()
     * @see         wp_ajax_hidden_columns()
     * @see         $admin_page_hooks
     */
    public function alterPageHook($title, $rawTitle, $context)
    {
        if ($rawTitle === static::getMenuTitle() && 'save' === $context) {
            $title = static::getMenuSlug();
        }

        return $title;
    }
}
