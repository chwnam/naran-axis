<?php


namespace Naran\Axis\Initiator;


use Naran\Axis\Object\Script;
use Naran\Axis\Object\Style;

use function Naran\Axis\Func\formatAttrs;

abstract class ScriptInitiator extends BaseInitiator
{
    protected $attrs = [
        'scripts' => [],
        'styles'  => [],
    ];

    public function initHooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'wpEnqueueScripts'], 5);

        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts'], 5);
        }
    }

    public function wpEnqueueScripts()
    {
        $this
            ->registerScripts($this->getCommonScripts())
            ->registerScripts($this->getFrontScripts())
            ->addLoaderFilters();
    }

    public function adminEnqueueScripts()
    {
        $this
            ->registerScripts($this->getCommonScripts())
            ->registerScripts($this->getAdminScripts())
            ->addLoaderFilters();
    }

    public function scriptLoaderTag($tag, $handle)
    {
        if (isset($this->attrs['scripts'][$handle]) && preg_match(
                ";(.*id='{$handle}-js')(></script>.*);ms",
                $tag,
                $matches
            )) {
            $attrs = formatAttrs($this->attrs['scripts'][$handle]);
            $tag   = "{$matches[1]}{$attrs}{$matches[2]}";
        }

        return $tag;
    }

    public function styleLoaderTag($tag, $handle)
    {
        if (isset($this->attrs['styles'][$handle]) && preg_match(';<link(.+)/>;', $tag, $matches)) {
            $attrs = formatAttrs($this->attrs['styles'][$handle]);
            $tag   = "<link{$matches[1]}{$attrs} />\n";
        }

        return $tag;
    }

    protected function registerScripts($items)
    {
        foreach ($items['scripts'] ?? [] as $script) {
            /** @var Script $script */
            if ($script->handle && $script->src) {
                wp_register_script(
                    $script->handle,
                    $script->src,
                    $script->deps,
                    $script->ver,
                    $script->inFooter
                );

                if ( ! empty($script->attrs)) {
                    $this->attrs['scripts'][$script->handle] = $script->attrs;
                }
            }
        }

        foreach ($items['styles'] ?? [] as $style) {
            /** @var Style $style */
            if ($style->handle && $style->src) {
                wp_register_style(
                    $style->handle,
                    $style->src,
                    $style->deps,
                    $style->ver,
                    $style->media
                );

                if ( ! empty($style->attrs)) {
                    $this->attrs['styles'][$style->handle] = $style->attrs;
                }
            }
        }

        return $this;
    }

    protected function addLoaderFilters()
    {
        if ($this->attrs['scripts']) {
            add_filter('script_loader_tag', [$this, 'scriptLoaderTag'], $this->getStarter()->getDefaultPriority(), 2);
        }

        if ($this->attrs['styles']) {
            add_filter('style_loader_tag', [$this, 'styleLoaderTag'], $this->getStarter()->getDefaultPriority(), 2);
        }

        return $this;
    }

    protected function getCommonScripts()
    {
        return [
            'scripts' => [],
            'styles'  => [],
        ];
    }

    protected function getFrontScripts()
    {
        return [
            'scripts' => [],
            'styles'  => [],
        ];
    }

    protected function getAdminScripts()
    {
        return [
            'scripts' => [],
            'styles'  => [],
        ];
    }

    protected function jsUrl($rel)
    {
        return $this->assetUrl($rel, 'js');
    }

    protected function cssUrl($rel)
    {
        return $this->assetUrl($rel, 'css');
    }

    protected function assetUrl($rel, $ext)
    {
        $starter = $this->getStarter();

        $paths = [
            get_stylesheet_directory() . "/{$starter->getSlug()}/assets/{$ext}",
            get_template_directory() . "/{$starter->getSlug()}/assets/{$ext}",
            dirname($starter->getMainFile()) . "/src/assets/{$ext}",
        ];

        $urls = [
            get_stylesheet_directory_uri() . "/{$starter->getSlug()}/assets/{$ext}",
            get_template_directory_uri() . "/{$starter->getSlug()}/assets/{$ext}",
            plugin_dir_url($starter->getMainFile()) . "src/assets/{$ext}",
        ];

        $debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
        $url   = '';

        foreach ($paths as $idx => $path) {
            $minified = $path . "/{$rel}.min.{$ext}";
            $raw      = $path . "/{$rel}.{$ext}";

            if ( ! $debug && file_exists($minified)) {
                $url = $urls[$idx] . "/{$rel}.min.{$ext}";
                break;
            } elseif (file_exists($raw)) {
                $url = $urls[$idx] . "/{$rel}.{$ext}";
                break;
            }
        }

        return $url;
    }
}
