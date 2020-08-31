<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\Starter\Starter;

class Tab extends SplitView
{
    public function __construct(
        Starter $starter,
        $queryParams = ['page' => ''],
        $parameter = 'tab',
        $template = 'generics/generic-tabs'
    ) {
        parent::__construct($starter);

        $this
            ->addQueryParameter($queryParams)
            ->setParameter($parameter)
            ->setTemplate($template);
    }

    public function renderItems()
    {
        $baseUrl   = $this->getBaseUrl();
        $current   = $this->getCurrent();
        $parameter = $this->getParameter();
        $tabs      = [];

        foreach ($this->getItems() as $slug => $item) {
            $tabs[$slug] = [
                'class' => 'nav-tab ' . ($current === $slug ? 'nav-tab-active' : ''),
                'url'   => add_query_arg($parameter, $slug, $baseUrl),
                'label' => $item['label'] ?? '',
            ];
        }

        $this->plainRender($this->getTemplate(), ['tabs' => $tabs]);
    }
}
