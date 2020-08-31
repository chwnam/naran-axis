<?php


namespace Naran\Axis\View;


/**
 * Class ShortcodeView
 *
 * @package Naran\Axis\View
 */
abstract class ShortcodeView extends View implements Dispatchable
{
    /**
     * ShortcodeView tag of this class.
     *
     * @var string
     */
    protected $shortcode;

    /**
     * Attribute of this shortcode.
     *
     * @var array
     */
    protected $atts = [];

    /**
     * Enclosed string between opening and closing shortcode tags.
     *
     * @var string
     */
    protected $enclosed;

    /**
     * Core handler method.
     *
     * @return string
     */
    abstract protected function processContent();

    /**
     * Default shortcode handler method.
     *
     * @return string
     */
    public function dispatch()
    {
        /**
         * @var array  $atts
         * @var string $enclosed
         * @var string $shortcode
         */
        [$atts, $enclosed, $shortcode] = func_get_args();

        add_filter(
            "shortcode_atts_{$shortcode}",
            [$this, 'filterAndValidateAtts'],
            $this->getStarter()->getDefaultPriority(),
            4
        );

        $this->shortcode = $shortcode;
        $this->atts      = shortcode_atts($this->getDefaultAtts($shortcode), $atts, $shortcode);
        $this->enclosed  = $enclosed;

        return $this->processContent();
    }

    /**
     * Return default shortcode atts.
     *
     * @override
     *
     * @param string $shortcode shortcode name.
     *
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getDefaultAtts($shortcode)
    {
        return [];
    }

    /**
     * Filter and validate atts. Filter is found in shortcode_atts().
     *
     * @override
     *
     * @callback
     * @filter       shortcode_atts_{$shortcode}
     *
     * @param array  $out
     * @param array  $default
     * @param array  $atts
     * @param string $shortcode
     *
     * @return array
     *
     * @see          shortcode_atts()
     * @noinspection PhpUnusedParameterInspection
     */
    public function filterAndValidateAtts($out, $default, $atts, $shortcode)
    {
        return $out;
    }
}
