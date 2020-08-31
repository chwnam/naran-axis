<?php


namespace Naran\Axis\View\Admin;


use Naran\Axis\Starter\Starter;
use Naran\Axis\View\Admin\FieldWidgets\FieldWidget;
use Naran\Axis\View\Dispatchable;
use Naran\Axis\View\View;
use function Naran\Axis\Func\toPascalCase;


/**
 * Class BaseSettingsView
 *
 * @package Naran\Axis\View
 */
abstract class BaseSettingsView extends View implements Dispatchable
{
    private $sections = [];

    private $fields = [];

    private $template = 'generics/generic-options';

    public function __construct(Starter $starter)
    {
        parent::__construct($starter);
    }

    abstract public function getOptionGroup();

    public function dispatch()
    {
        $this->prepareSettings();
        $this->addSettingsSections();
        $this->addSettingsFields();

        $this
            ->enqueueScript('axis-field-widget')
            ->enqueueStyle('axis-field-widget')
            ->plainRender(
                $this->getTemplate(),
                [
                    'option_group' => $this->getOptionGroup(),
                ]
            );
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

    public function addSection($id, $title, $callback = null, $page = null)
    {
        $id = sanitize_key($id);

        if ( ! $callback) {
            if (is_callable([$this, 'renderSection' . toPascalCase($id)])) {
                $callback = [$this, 'renderSection' . toPascalCase($id)];
            } else {
                $callback = '__return_empty_string';
            }
        }

        $this->sections[$id] = [
            'id'       => $id,
            'title'    => $title,
            'callback' => $callback,
            'page'     => $page ? $page : $this->getOptionGroup(),
        ];

        return $this;
    }

    public function getSection($id)
    {
        return $this->sections[$id] ?? null;
    }

    public function removeSection($id)
    {
        unset($this->sections[$id]);
    }

    public function resetSections()
    {
        $this->sections = [];
    }

    public function getSections()
    {
        return $this->sections;
    }

    public function addField($section, $widget, $title = '', $callback = null, $page = null, $args = [])
    {
        if (is_string($widget) && $title && is_callable($callback)) {
            $id = sanitize_key($widget);
        } elseif ($widget instanceof FieldWidget) {
            $id       = $widget->getKey();
            $title    = $widget->getLabel();
            $labelFor = $widget->label_for;

            if ( ! $callback) {
                if (is_callable([$this, 'renderField' . toPascalCase($id)])) {
                    $callback = [$this, 'renderField' . toPascalCase($id)];
                } else {
                    $callback = [$this, 'defaultRenderField'];
                }
            }

            if ($labelFor && ! isset($args['label_for'])) {
                $args['label_for'] = $labelFor;
            }

            $args['widget'] = $widget;
        }

        if ( ! empty($id)) {
            $this->fields[$id] = [
                'id'       => $id,
                'title'    => $title,
                'callback' => $callback,
                'page'     => $page ? $page : $this->getOptionGroup(),
                'section'  => $section,
                'args'     => $args,
            ];
        }

        return $this;
    }

    public function getField($id)
    {
        return $this->fields[$id] ?? null;
    }

    public function removeField($id)
    {
        unset($this->fields[$id]);
    }

    public function resetFields()
    {
        $this->fields = [];
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function defaultRenderField($args)
    {
        $field = $args['widget'] ?? false;

        if ($field) {
            $field->renderWidget();
            $field->renderDescription();
        }
    }

    protected function addSettingsSections()
    {
        foreach ($this->getSections() as $section) {
            add_settings_section(
                $section['id'],
                $section['title'],
                $section['callback'],
                $section['page']
            );
        }
    }

    protected function addSettingsFields()
    {
        foreach ($this->getFields() as $field) {
            add_settings_field(
                $field['id'],
                $field['title'],
                $field['callback'],
                $field['page'],
                $field['section'],
                $field['args'],
            );
        }
    }
}