<?php


namespace Naran\Axis\View;


use InvalidArgumentException;
use Naran\Axis\Object\Component;
use Naran\Axis\Object\ComponentTrait;
use Naran\Axis\Object\Nonce;
use Naran\Axis\Starter\Starter;
use Parsedown;
use ParsedownExtra;

use function Naran\Axis\Func\strStartsWith;
use function Naran\Axis\Func\toCamelCase;
use function Naran\Axis\Func\toSnakeCase;


/**
 * Class BaseView
 *
 * @package Naran\Axis\View
 */
class View implements Component
{
    protected static $templates = [];

    protected static $ejsTmpl = [];

    use ComponentTrait;

    public function __construct(Starter $starter)
    {
        $this->starter = $starter;
    }

    /**
     * Render a template file.
     *
     * @param string     $template Template name.
     * @param array      $context  Context array.
     * @param bool|array $return   array
     *                             - bool   return    Return or echo output
     *                             - bool   useBlade  Use blade template engine, or classical including file.
     *                             - string ext       Available only when useBlade=false. Defaults to 'php'.
     *                             - bool   _builtin  When true, template name is only searched in Axis directories.
     *
     * @return false|string|null
     */
    public function render($template, $context = [], $return = false)
    {
        $template = trim($template, '\\/');

        if (is_array($return)) {
            $return = wp_parse_args(
                $return,
                [
                    'return'   => false,
                    'useBlade' => $this->getStarter()->useBlade(),
                    'ext'      => 'php',
                    '_builtin' => false,
                ]
            );
        } else {
            $return = [
                'return'   => boolval($return),
                'useBlade' => $this->getStarter()->useBlade(),
                '_builtin' => false,
            ];
        }

        if ($return['useBlade']) {
            try {
                $output = $this->getContainer()->get('view')->make($template, $context)->render();
                if ($return['return']) {
                    return $output;
                } else {
                    echo $output;

                    return null;
                }
            } catch (InvalidArgumentException $e) {
                echo '<div class=""><p>Render Error: ' . esc_html($e->getMessage()) . '</p></div>';
            }
        } else {
            $path = static::findFile($template, $return['ext'], $return['_builtin']);
            if ($path) {
                return static::renderFile($path, $context, $return['return']);
            } else {
                static::templateNotFound($template);
            }
        }

        return null;
    }

    /**
     * Perform simple PHP file include.
     *
     * @param string $template Template name.
     * @param array  $context  Context array.
     * @param false  $return   Return, or echo.
     *
     * @return string|null
     */
    public function plainRender($template, $context = [], $return = false)
    {
        return $this->render($template, $context, ['return' => $return, 'useBlade' => false]);
    }

    /**
     * @param string $identifier
     *
     * @return Nonce
     */
    public function getNonce($identifier = '')
    {
        if ( ! $identifier) {
            $class      = get_called_class();
            $pos        = strrpos($class, '\\');
            $identifier = toSnakeCase(false !== $pos ? substr($class, $pos + 1) : $class);
        }

        return $this->resolve(Nonce::class, ['identifier' => $identifier]);
    }

    /**
     * Enqueue underscore EJS-style template.
     *
     * @param string $template Template name.
     * @param array  $context  PHP template context.
     * @param null   $tmplId   Template ID.
     * @param false  $_builtin
     *
     * @return self
     */
    public function enqueueEjs($template, $context = [], $tmplId = null, $_builtin = false)
    {
        static::$ejsTmpl[$template] = [$context, $tmplId, $_builtin];

        $tag = is_admin() ? 'admin_print_footer_scripts' : 'wp_print_footer_scripts';

        if ( ! has_action($tag, [$this, 'printEjsTemplates'])) {
            add_action($tag, [$this, 'printEjsTemplates'], 5);
        }

        return $this;
    }

    /**
     * Print enqueued EJS templates.
     *
     * @callback
     * @action   admin_print_footer_scripts
     * @action   wp_print_footer_scripts
     */
    public function printEjsTemplates()
    {
        foreach (static::$ejsTmpl as $template => [$context, $tmplId, $_builtin]) {
            $content = $this->render(
                $template,
                $context,
                [
                    'return'   => true,
                    'useBlade' => false,
                    'ext'      => 'ejs',
                    '_builtin' => $_builtin
                ]
            );

            if ($content) {
                if ( ! $tmplId) {
                    $tmplId = wp_basename($template);
                }
                if ( ! strStartsWith($tmplId, 'tmpl-')) {
                    $tmplId = 'tmpl-' . $tmplId;
                }
                $tmplId = esc_attr($tmplId);

                echo "\n<script id='{$tmplId}' type='text/template'>\n{$content}\n</script>\n";
            }
        }

        static::$ejsTmpl = [];
    }

    /**
     * Enqueue script, append localization and insert inline JS code.
     *
     * $i18n variable is named from its handle, in camel-cased form.
     *
     * @param string $handle   Script handle.
     * @param array  $i18n     Localization array.
     * @param string $inline   Inline JS code.
     * @param string $position 'after', or 'before'.
     *
     * @return $this
     */
    protected function enqueueScript($handle, $i18n = [], $inline = '', $position = 'after')
    {
        if (wp_script_is($handle, 'registered')) {
            wp_enqueue_script($handle);

            if ( ! empty($i18n)) {
                wp_localize_script($handle, toCamelCase(str_replace('-', '_', $handle)), $i18n);
            }

            if ( ! empty($inline)) {
                wp_add_inline_script($handle, $inline, $position);
            }
        }

        return $this;
    }

    /**
     * Enqueue style and append inline CSS code.
     *
     * @param string $handle Style handle.
     * @param string $inline Inline CSS code.
     *
     * @return $this
     */
    protected function enqueueStyle($handle, $inline = '')
    {
        if (wp_style_is($handle, 'registered')) {
            wp_enqueue_style($handle);

            if ( ! empty($inline)) {
                wp_add_inline_style($handle, $inline);
            }
        }

        return $this;
    }

    /**
     * Resolve a file.
     *
     * @param string $template Template name.
     * @param string $ext      File extension with heading dot.
     * @param false  $_builtin Search only in Axis directories if true.
     *
     * @return string|null
     */
    protected function findFile($template, $ext = 'php', $_builtin = false)
    {
        $template = trim($template, '\\/');
        $ext      = trim($ext, '.');

        if (isset(static::$templates["{$template}.{$ext}"])) {
            return static::$templates["{$template}.{$ext}"];
        } else {
            $path = null;

            if ($_builtin) {
                $path = plugin_dir_path(AXIS_MAIN) . "/src/templates/{$template}.{$ext}";
            } else {
                $paths = [
                    get_stylesheet_directory() . "/{$this->getStarter()->getSlug()}/templates/{$template}.{$ext}",
                    get_template_directory() . "/{$this->getStarter()->getSlug()}/templates/{$template}.{$ext}",
                    dirname($this->getStarter()->getMainFile()) . "/src/templates/{$template}.{$ext}",
                    dirname(AXIS_MAIN) . "/src/templates/{$template}.{$ext}",
                ];
                foreach ($paths as $p) {
                    if (file_exists($p) && is_file($p) && is_readable($p)) {
                        $path = $p;
                        break;
                    }
                }
            }

            if ($path) {
                static::$templates["{$template}.{$ext}"] = $path;
            }

            return $path;
        }
    }

    /**
     * Render a file.
     *
     * @param string $__tmpl__ Full path to a template file.
     * @param array  $__ctx__  Template context.
     * @param bool   $__ret__  Return or echo.
     *
     * @return string|null
     */
    public static function renderFile($__tmpl__, $__ctx__ = [], $__ret__ = false)
    {
        if ($__ret__) {
            ob_start();
        }

        extract($__ctx__, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        include $__tmpl__;

        return $__ret__ ? ob_get_clean() : null;
    }

    public static function renderMarkdown($file, $containerId, $contentFilter = null, $useMarkdownExtra = true)
    {
        if (file_exists($file) && is_file($file) && is_readable($file)) {
            $parsedown = $useMarkdownExtra ? new ParsedownExtra() : new Parsedown();
            $content   = file_get_contents($file);
            if (is_callable($contentFilter)) {
                $content = call_user_func($contentFilter, $content, $file);
            }
            $containerId = esc_attr($containerId);
            echo "\n<div id='{$containerId}' class='markdown-body'>\n{$parsedown->parse($content)}\n</div>\n";
        } else {
            static::templateNotFound($file, '');
        }

        // todo: syntax highlight (prism or codemirror)
        // todo: github markdown style.
    }

    /**
     * Template not found message.
     *
     * @param string $template Template name.
     * @param string $ext      File extension with heading dot.
     */
    protected static function templateNotFound($template, $ext = '.php')
    {
        echo '<div class=""><p>';
        echo esc_html(sprintf(__('Render error: template [%s%s] not found.', 'naran-axis'), $template, $ext));
        echo '</p></div>';
    }
}
