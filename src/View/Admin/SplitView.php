<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\View\View;
use Naran\Axis\View\Dispatchable;

abstract class SplitView extends View
{
    private $items = [];

    private $parameter = '';

    private $template = '';

    private $baseUrl = '';

    private $queryParameters = [];

    abstract protected function renderItems();

    public function dispatch()
    {
        $this->renderItems();

        $item       = $this->getCurrentITem();
        $view       = $item['view'] ?? null;
        $parameters = $item['parameters'] ?? [];

        // 'class@method' string to an array like [class, method].
        if (is_string($view) && strpos($view, '@')) {
            $view = explode('@', $view, 2);
        }

        if (is_array($view) && 2 === count($view)) {
            $abstract = $view[0];
            $method   = $view[1];
            $instance = $this->resolve($abstract, $parameters);

            if ($instance && method_exists($instance, $method)) {
                call_user_func([$instance, $method]);
            }
        } elseif (is_callable($view)) {
            call_user_func($view, $parameters);
        } elseif (
            // Derivatives of DispatchView.
            class_exists($view) &&
            ($implemented = class_implements($view)) &&
            (isset($implemented[Dispatchable::class])
            )
        ) {
            /** @var Dispatchable $instance */
            $instance = $this->resolve($view, $parameters);
            $instance->dispatch();
        }
    }

    public function getCurrent()
    {
        $param = $this->getParameter();

        if (isset($_GET[$param])) {
            return sanitize_key($_GET[$param]);
        } else {
            return key($items = $this->getItems());
        }
    }

    public function getCurrentItem()
    {
        return $this->items[$this->getCurrent()] ?? [];
    }

    public function addItem($slug, $label, $view, $args = [])
    {
        $slug = sanitize_key($slug);

        if ($slug) {
            $this->items[$slug] = [
                'label'      => $label,
                'view'       => $view,
                'parameters' => $args,
            ];
        }

        return $this;
    }

    public function removeItem($slug)
    {
        unset($this->items[$slug]);

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function setParameter($parameter)
    {
        $this->parameter = sanitize_key($parameter);

        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    public function getBaseUrl()
    {
        if ( ! $this->baseUrl) {
            $this->setBaseUrl($_SERVER['REQUEST_URI'] ?? '');
        }

        return $this->baseUrl;
    }

    public function setBaseUrl($url)
    {
        $parsed = parse_url($url);

        if (isset($parsed['query']) && $parsed['query']) {
            parse_str($parsed['query'], $query);
            $this->baseUrl = add_query_arg(array_intersect_key($query, $this->getQueryParameters()), $parsed['path']);
        } else {
            $this->baseUrl = add_query_arg($this->getQueryParameters(), $parsed['path']);
        }

        return $this;
    }

    public function addQueryParameter($key, $value = '')
    {
        $parameter = $this->getParameter();

        if (is_array($key) && empty($value)) {
            foreach ($key as $k => $v) {
                $k = sanitize_key($k);
                if ($k && $parameter !== $k) {
                    $this->queryParameters[$k] = $v;
                }
            }
        } elseif (is_string($key)) {
            $key = sanitize_key($key);
            if ($key && $parameter !== $key) {
                $this->queryParameters[$key] = $value;
            }
        }

        return $this;
    }

    public function removeQueryParameter($key)
    {
        unset($this->queryParameters[$key]);

        return $this;
    }

    public function getQueryParameters()
    {
        return $this->queryParameters;
    }
}
