<?php


namespace Naran\Axis\View\Admin\FieldWidgets;


use function Naran\Axis\Func\inputTag;

/**
 * Class InputWidget
 *
 * @package Naran\Axis\View\Admin\FieldWidgets
 *
 * @property-read array $attrs InputWidget tag attributes.
 */
class InputWidget extends FieldWidget
{
    public function renderWidgetCore()
    {
        $attrs = wp_parse_args(
            $this->attrs,
            [
                'id'       => $this->getId(),
                'name'     => $this->getName(),
                'value'    => $this->getValue(),
                'class'    => 'text axis-field-widget axis-input',
                'type'     => 'text',
                'required' => $this->field->required,
                'title'    => $this->field->required ? $this->field->required_message : '',
            ]
        );

        inputTag($attrs);
    }

    public static function getDefaultArgs()
    {
        return array_merge(
            parent::getDefaultArgs(),
            [
                'attrs' => []
            ]
        );
    }
}
