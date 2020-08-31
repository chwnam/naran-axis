<?php


namespace Naran\Axis\View\Admin;


/**
 * Class SubmenuPageView
 *
 * @package Naran\Axis\View\Admin
 */
abstract class SubmenuPageView extends MenuPageView
{
    /**
     * Return parent menu slug.
     *
     * @return string
     */
    abstract public static function getParentSlug();

    /**
     * @override
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

        $this->hook = add_submenu_page(
            static::getParentSlug(),
            static::getPageTitle(),
            static::getMenuTitle(),
            static::getCapability(),
            static::getMenuSlug(),
            $this->getRenderCallback(),
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
}
