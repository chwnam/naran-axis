<?php


namespace Naran\Axis\Object;


use InvalidArgumentException;
use Naran\Axis\Starter\Starter;

final class Nonce
{
    /** @var Starter */
    private $starter;

    /** @var string */
    private $identifier;

    /**
     * Temporary storage for callback method.
     *
     * @var string
     */
    private $referer;

    public function __construct(Starter $starter, string $identifier)
    {
        $this->starter    = $starter;
        $this->identifier = sanitize_key($identifier);

        if ( ! $this->identifier) {
            throw new InvalidArgumentException(__('InputWidget a valid identifier.', 'naran-axis'));
        }
    }

    public function getQueryArg()
    {
        return $this->starter->getSlug() . '_nonce_' . $this->identifier;
    }

    public function getAction()
    {
        return $this->starter->getSlug() . '_action_' . $this->identifier;
    }

    public function getNonce()
    {
        return wp_create_nonce($this->getAction());
    }

    public function verify($token)
    {
        return wp_verify_nonce($token, $this->getAction());
    }

    public function check($referer = null)
    {
        $this->referer = $referer;
        $callback      = [$this, 'checkReferer'];

        if (wp_doing_ajax()) {
            if ($referer) {
                add_action('check_ajax_referer', $callback, $this->starter->getDefaultPriority(), 2);
            }

            check_ajax_referer($this->getAction(), $this->getQueryArg());

            if ($referer) {
                remove_action('check_ajax_referer', $callback, $this->starter->getDefaultPriority());
            }
        } else {
            if ($referer) {
                add_action('check_admin_referer', $callback, $this->starter->getDefaultPriority(), 2);
            }

            check_admin_referer($this->getAction(), $this->getQueryArg());

            if ($referer) {
                add_action('check_admin_referer', $callback, $this->starter->getDefaultPriority());
            }
        }
    }

    /**
     * Check referer.
     *
     * @callback
     * @action check_admin_referer
     * @action check_ajax_referer
     *
     * @param string       $action
     * @param string|false $result
     */
    public function checkReferer($action, $result)
    {
        if ($this->getAction() === $action && false !== $result) {
            $expected = parse_url(esc_url_raw($this->referer));
            $actual   = parse_url(wp_get_referer());

            $scheme = $expected['scheme'] == $actual['scheme'];
            $host   = $expected['host'] == $actual['host'];
            $path   = $expected['path'] == $actual['path'];

            if ( ! $scheme || ! $host || ! $path) {
                wp_die(-1, 403);
            }

            parse_str($expected['query'], $eq);
            parse_str($actual['query'], $aq);

            $output = true;

            foreach ($aq as $k => $v) {
                if ( ! isset($eq[$k]) || $eq[$k] != $aq[$k]) {
                    $output = false;
                    break;
                }
            }

            if ( ! $output) {
                wp_die(-1, 403);
            }
        }
    }
}
