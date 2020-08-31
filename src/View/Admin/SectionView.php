<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\Starter\Starter;

class SectionView extends SplitView
{
    public function __construct(
        Starter $starter,
        $queryParams = ['page' => '', 'tab' => ''],
        $parameter = 'section',
        $template = 'generics/generic-sections'
    ) {
        parent::__construct($starter);

        $this->addQueryParameter($queryParams)
             ->setParameter($parameter)
             ->setTemplate($template);
    }

    public function renderItems()
    {
        $baseUrl   = $this->getBaseUrl();
        $current   = $this->getCurrent();
        $parameter = $this->getParameter();
        $sections  = [];

        foreach ($this->getItems() as $slug => $item) {
            $sections[] = [
                'class' => $current === $slug ? 'current' : '',
                'url'   => add_query_arg($parameter, $slug, $baseUrl),
                'label' => $item['label'] ?? '',
            ];
        }

        $this->plainRender($this->getTemplate(), ['sections' => $sections]);
    }
}