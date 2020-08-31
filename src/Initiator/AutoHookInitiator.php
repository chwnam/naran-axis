<?php


namespace Naran\Axis\Initiator;

use Illuminate\Contracts\Container\BindingResolutionException;
use Naran\Axis\View\Dispatchable;

use function Naran\Axis\Func\strStartsWith;

/**
 * Class AutoHookInitiator
 *
 * Automatically add action, filter, shortcode, activation, deactivation hook by parsing its method name.
 *
 * @package Naran\Axis\Initiator
 *
 * @example public function action_init() {}             -- add_action( 'init', {$callback} );
 *          public function filter_10_query_var() {}     -- add_filter( 'query_var', {$callback}, 10 );
 *          public function action_10_3_save_post() {}   -- add_action( 'save_post', {$callback}, 10, 3 );
 *          public function shortcode_my_code() {}       -- add_shortcode( 'my_code', {$callback} );
 *          public function activation()                 -- register_activation_hook( MAIN, {$callback} );
 *          public function deactivation()               -- register_deactivation_hook( MAIN, {$callback} );
 */
class AutoHookInitiator extends BaseInitiator
{
    protected static $ignoredMethods = [
        '__construct',
        'addDirective',
        'addReplacement',
        'formArguments',
        'getCallbackParams',
        'getMethods',
        'handleDirective',
        'handleReplacement',
        'initHooks',
    ];

    protected static $directives = [
        'allow_nopriv' => [__CLASS__, 'handleDirectiveAllowNopriv']
    ];

    private $defaultPriority;

    private $replacements = [];

    public function initHooks()
    {
        $this->defaultPriority = $this->getStarter()->getDefaultPriority();

        $file = plugin_basename($this->getStarter()->getMainFile());

        foreach ($this->getCallbackParams() as $function => $params) {
            foreach ($params as $param) {
                switch ($function) {
                    case 'add_action':
                        add_action(
                            $param['tag'],
                            $param['callback'],
                            $param['priority'],
                            $param['accepted_args']
                        );
                        break;

                    case 'add_filter':
                        add_filter(
                            $param['tag'],
                            $param['callback'],
                            $param['priority'],
                            $param['accepted_args']
                        );
                        break;

                    case 'add_shortcode':
                        add_shortcode($param['tag'], $param['callback']);
                        break;

                    case 'register_activation_hook';
                        add_action("activate_{$file}", $param['callback'], $param['priority']);
                        break;

                    case 'register_deactivation_hook':
                        add_action("deactivate_{$file}", $param['callback'], $param['priority']);
                        break;
                }
                $this->handleDirective($function, $params);
            }
        }
    }

    /**
     * Return all methods callback param.
     *
     * @return array<string, array>
     */
    public function getCallbackParams()
    {
        $patterns = [
            //                                                    p.       a.      h.      d.
            //                              1    2              3 4      5 6       7    8  9
            'action|filter'           => '/^(v_)?(action|filter)(_(\d+))?(_(\d+))?_(.+?)(__(.+))?$/',

            //                              1              2    3  4
            'shortcode'               => '/^(v_)?shortcode_(.+?)(__(.+))?$/',

            //                              1    2                        3  4     5  6
            'activation|deactivation' => '/^(v_)?(activation|deactivation)(_(\d+))?(__(.+))?$/',
        ];

        $output = [
            'add_action'                 => [],
            'add_filter'                 => [],
            'add_shortcode'              => [],
            'register_activation_hook'   => [],
            'register_deactivation_hook' => [],
        ];

        foreach ($this->getMethods() as $method) {
            if (8 > strlen($method) || '_' === $method[0]) {
                continue;
            }

            foreach ($patterns as $type => $pattern) {
                if (preg_match($pattern, $method, $match)) {
                    switch ($type) {
                        case 'action|filter':
                            $output["add_{$match[2]}"][] = [
                                'tag'           => $this->handleReplacement($match[7], $method),
                                'callback'      => $this->getViewCallback('v_' === $match[1], $method),
                                'priority'      => $match[4] ? intval($match[4]) : $this->defaultPriority,
                                'accepted_args' => $match[6] ? intval($match[6]) : 1,
                                'directive'     => $match[9] ?? '',
                            ];
                            break 2;

                        case 'shortcode':
                            $output['add_shortcode'][] = [
                                'tag'       => $this->handleReplacement($match[2], $method),
                                'callback'  => $this->getViewCallback('v_' === $match[1], $method),
                                'directive' => $match[4] ?? '',
                            ];
                            break 2;

                        case 'activation|deactivation':
                            $output["register_{$match[2]}_hook"][] = [
                                'callback'  => $this->getViewCallback('v_' === $match[1], $method),
                                'priority'  => $match[4] ? intval($match[4]) : $this->defaultPriority,
                                'directive' => $match[4] ?? '',
                            ];
                            break 2;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Return method names of current class.
     *
     * @return string[]
     */
    public function getMethods()
    {
        return array_diff(get_class_methods($this), static::$ignoredMethods);
    }

    /**
     * Add directive.
     *
     * @param string $directive Name of directive. Allows only lowercase alphabet, numbers, and underscore.
     * @param string $method    Directive's method. Argument 0: 1:
     */
    public static function addDirective($directive, $method)
    {
        $directive = str_replace('-', '_', sanitize_key($directive));
        if ($directive && is_callable($method)) {
            static::$directives[$directive] = $method;
        }
    }

    /**
     * Process directives
     *
     * @param string $function Function name.
     *                         e.g. add_action, add_filter, add_shortcode, add_shortcode, register_activation,
     *                         register_deactivation.
     * @param array  $params   Parsed parameter data array.
     */
    protected function handleDirective($function, $params)
    {
        $method = static::$directives[$params['directive'] ?? false] ?? false;
        if ($method) {
            call_user_func_array($method, [$function, $params]);
        }
    }

    /**
     * Add replacement.
     *
     * A 'tag name' part is a string so it can be dynamic, like 'save_post_{$post_type}'.
     * With replacement, your fixed class method name is replaced in run time.
     *
     * @param string          $searchFor      A string part in method name.
     * @param string|callable $replaceTo      A string replacement that converted from $methodPart.
     *                                        When it is callable, two arguments are given.
     *                                        - 0th: $methodNamePart
     *                                        - 1st: full method name
     *
     * @example public function action_10_3_save_post_dynamic_portion( $postId, $post, $updated ) { }
     *
     *          $this->addReplacement('save_post_dynamic_portion', function( $method ) {
     *             // add_action(
     *             //   'save_post_' . get_my_post_type(),
     *             //   [$this, 'action_10_3_save_post_dynamic_portion' ], 10, 3
     *             // );
     *             return 'save_post_' . get_my_post_type();
     *          } );
     *
     */
    protected function addReplacement($searchFor, $replaceTo)
    {
        $this->replacements[$searchFor] = $replaceTo;
    }

    /**
     * Process replacement.
     *
     * @param string $searchFor  A raw string found in method name.
     * @param string $methodName Full method name.
     *
     * @return string
     */
    protected function handleReplacement($searchFor, $methodName)
    {
        $replaceTo = $this->replacements[$searchFor] ?? false;

        if ($replaceTo) {
            if (is_callable($replaceTo)) {
                return call_user_func_array($replaceTo, [$searchFor, $methodName]);
            } else {
                return $replaceTo;
            }
        } else {
            return $searchFor;
        }
    }

    /**
     * @param bool   $isVirtual
     * @param string $method
     *
     * @return callable
     */
    protected function getViewCallback($isVirtual, $method)
    {
        if ($isVirtual) {
            // array( 'abstract', 'method', $params )
            // array( $instance, 'method', $params )
            $realCallback = call_user_func([$this, $method]);
            try {
                $container = $this->getStarter()->getContainer();
                if (is_array($realCallback) && 2 <= count($realCallback) && is_string($realCallback[0])) {
                    $container->bindIf($realCallback[0], null, true);
                    $params   = $realCallback[2] ?? null;
                    $callback = [$container->make($realCallback[0], $params), $realCallback[1]];
                } elseif (
                    class_exists($realCallback) &&
                    ($implements = class_implements($realCallback)) &&
                    isset($implements[Dispatchable::class])
                ) {
                    $callback = [$container->make($realCallback), 'dispatch'];
                } else {
                    $callback = $realCallback;
                }
            } catch (BindingResolutionException $e) {
                $callback = $realCallback;
            }
        } else {
            $callback = [$this, $method];
        }

        return $callback;
    }

    /**
     * Process 'allow_nopriv' directive.
     *
     * @param string $function Function name, like add_action, add_filter, ...
     * @param array  $param    Parameter parsed from method name.
     *
     * @see initHooks()
     * @see getCallbackParams()
     */
    protected static function handleDirectiveAllowNopriv($function, $param)
    {
        if ($function === 'add_action') {
            if (strStartsWith($param['tag'], 'wp_ajax_')) {
                add_action(
                    str_replace('wp_ajax_', 'wp_ajax_nopriv_', $param['tag']),
                    $param['callback'],
                    $param['priority'],
                    $param['accepted_args']
                );
            } elseif (strStartsWith($param['tag'], 'admin_post_')) {
                add_action(
                    str_replace('admin_post_', 'admin_post_nopriv_', $param['tag']),
                    $param['callback'],
                    $param['priority'],
                    $param['accepted_args']
                );
            }
        }
    }
}
