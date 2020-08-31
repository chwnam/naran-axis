<?php


namespace Naran\Axis\View\Admin\FieldWidgets;


use BadMethodCallException;
use Naran\Axis\Model\Field\Field;
use Naran\Axis\Model\Field\Meta;
use Naran\Axis\Model\Field\Option;
use Naran\Axis\Model\Field\Stub;
use Naran\Axis\Model\Holder\OptionHolder;
use Naran\Axis\View\View;


/**
 * Class FieldWidget
 *
 * @package Naran\Axis\View\Admin\FieldWidgets
 *
 * @property-read string          $description
 * @property-read string|bool     $output_desc         Output description. 'p' will echo p tag instead of span tag.
 * @property-read string          $before_desc         Accepts 'br' or 'spacer'.
 * @property-read bool            $tooltip
 * @property-read string          $label
 * @property-read string|false    $label_for           An empty string to use field key. 'false' for disabling.
 * @property-read bool            $prefer_short_label  Use short label or not.
 * @property-read null|int        $object_id
 * @property-read null|string     $context
 * @property-read string|string[] $th_class
 * @property-read string          $th_style
 * @property-read string|string[] $td_class
 * @property-read string          $td_style
 * @property-read callable|string $before
 * @property-read callable|string $after
 * @property-read string          $key_postfix
 * @property-read callable|null   $getter
 */
abstract class FieldWidget extends View
{
    /** @var Field */
    protected $field;

    protected $args = [];

    /**
     * FieldWidget constructor.
     *
     * @param Field $field
     * @param array $args
     */
    public function __construct(Field $field, array $args = [])
    {
        parent::__construct($field->getStarter());

        $this->field = $field;
        $this->args  = wp_parse_args($args, static::getDefaultArgs());
    }

    public function __get($property)
    {
        if (array_key_exists($property, $this->args)) {
            return $this->args[$property];
        } elseif ('field' === $property) {
            return $this->field;
        }

        throw new BadMethodCallException("Property '{$property}' is invalid.");
    }

    /**
     * Render core part of this widget.
     *
     * @return void
     */
    abstract public function renderWidgetCore();

    public function renderWidget()
    {
        $this->beforeRenderWidget();
        $this->renderWidgetCore();
        $this->renderContext();
        $this->afterRenderWidget();
        $this->renderDescription();
    }

    public function beforeRenderWidget()
    {
        $this->renderCallback($this->before);
    }

    public function afterRenderWidget()
    {
        $this->renderCallback($this->after);
    }

    /**
     * Option field context.
     *
     * @see OptionHolder::correctContextual()
     */
    public function renderContext()
    {
        if ($this->context && $this->field instanceof Option && $this->field->contextual) {
            $id    = esc_attr('axis_field_widget_context[' . $this->field->key . ']');
            $name  = esc_attr('axis_field_widget_context[' . $this->field->key . ']');
            $value = esc_attr($this->context);

            echo "<input type='hidden' id='{$id}' name='{$name}' value='{$value}'>\n";
        }
    }

    public function renderTr()
    {
        echo "<tr>\n";
        $this->renderTh();
        $this->renderTd();
        echo "</tr>\n";
    }

    public function renderTh()
    {
        $class = implode(
            ' ',
            array_map(
                'sanitize_html_class',
                is_string($this->th_class) ? preg_split('/\s+/', $this->th_class) : $this->th_class
            )
        );

        $style = esc_attr($this->th_style);
        $for   = $this->getLabelFor();

        echo "<th class='{$class}' style='{$style}' scope='row'>\n";
        echo "    <label for='{$for}'>" . $this->getTitle() . "</label>\n";
        echo "</th>\n";
    }

    public function renderTd()
    {
        $class = implode(
            ' ',
            array_map(
                'sanitize_html_class',
                is_string($this->td_class) ? preg_split('/\s+/', $this->td_class) : $this->td_class
            )
        );

        $style = esc_attr($this->td_style);

        echo "<td class='{$class}' style='{$style}'>\n";
        $this->renderWidget();
        echo "</td>\n";
    }

    public function renderDescription()
    {
        $outputDesc  = $this->output_desc;
        $description = $this->description;

        if ($outputDesc && $description) {
            $beforeDesc = $this->before_desc;

            if ('br' === $beforeDesc) {
                echo '<br>';
            } elseif ('spacer' === $beforeDesc) {
                echo '<span class="spacer"></span>';
            }

            printf(
                '<%1$s class="description">%2$s</%1$s>',
                ('p' === $outputDesc) ? 'p' : 'span',
                wp_kses_post($description)
            );
        }
    }

    public function getKey()
    {
        return $this->field->key;
    }

    public function getId()
    {
        return $this->field->key . $this->key_postfix;
    }

    public function getName()
    {
        return $this->field->key . $this->key_postfix;
    }

    public function getValue()
    {
        if ($this->field instanceof Meta) {
            $objectId = $this->getObjectId();
            $value    = $objectId ? $this->field->get($objectId) : $this->field->default;
        } elseif ($this->field instanceof Option) {
            $value = $this->field->contextual ? $this->field->get($this->context) : $this->field->get(null);
        } elseif ($this->field instanceof Stub) {
            $value = $this->field->default;
        }

        if (empty($value)) {
            return null;
        }

        if (is_array($value) && $this->key_postfix) {
            $postfix = array_reverse(explode('][', trim($this->key_postfix, '[]')));
            while ($postfix) {
                $k = array_pop($postfix);
                if (isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    $value = false;
                    break;
                }
            }
        }

        if ( ! is_scalar($value) && ! is_callable($this->getter)) {
            throw new BadMethodCallException(
                __(
                    'The value is not a scalar type. To properly pass the value to the widget, please pass \'getter\' callback.',
                    'naran-axis'
                )
            );
        }

        return is_scalar($value) ? $value : call_user_func($this->getter, $value, $this);
    }

    /**
     * Return title, including label text, required text, and tooltip.
     *
     * e.g. LABEL <span>[required]</span> <span class="axis-widget-tooltip"></span>
     */
    public function getTitle()
    {
        return $this->getLabel() .
               ($this->field->required ? $this->getRequired() : '') .
               $this->getTooltip();
    }

    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        } else {
            return $this->prefer_short_label ? $this->field->short_label : $this->field->label;
        }
    }

    /**
     * Return label tag's 'for' attribute value.
     *
     * @return string
     */
    public function getLabelFor()
    {
        $for = $this->label_for;

        if (false === $for) {
            return '';
        } else {
            return $for ? $for : $this->getId();
        }
    }

    /**
     * Return widget description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description ? $this->description : $this->field->description;
    }

    /**
     * Return tooltip HTML code.
     */
    public function getTooltip()
    {
        $tooltip = $this->tooltip;
        $code    = '';

        if ($tooltip) {
            $defaultAttrs = ['class' => true, 'style' => true];
            $ksesAllowed  = [
                'br'     => &$defaultAttrs,
                'em'     => &$defaultAttrs,
                'strong' => &$defaultAttrs,
                'small'  => &$defaultAttrs,
                'span'   => &$defaultAttrs,
                'ul'     => &$defaultAttrs,
                'li'     => &$defaultAttrs,
                'ol'     => &$defaultAttrs,
                'p'      => &$defaultAttrs,
                'a'      => ['href' => true, 'target' => true, 'class' => true, 'style' => true],
            ];

            $code = htmlspecialchars(
                wp_kses(html_entity_decode($this->description), $ksesAllowed),
                ENT_COMPAT | ENT_QUOTES
            );
        }

        return $code ? sprintf(
            '<span class="dashicons dashicons-editor-help axis-widget-tooltip" data-tooltip="%s"></span><div class="wp-clearfix"></div>',
            $code
        ) : '';
    }

    /**
     * Return 'required' string.
     *
     * @return string
     */
    public function getRequired()
    {
        return '<span class="axis-widget-required">[' . esc_html__('required', 'naran-axis') . ']</span>';
    }

    /**
     * Call function or echo string as-is.
     *
     * @param callable|string $render
     *
     * @used-by beforeRenderWidget()
     * @used-by afterRenderWidget()
     */
    protected function renderCallback($render)
    {
        if (is_callable($render)) {
            call_user_func($render, $this);
        } elseif (is_string($render)) {
            echo $render;
        }
    }

    protected function getObjectId()
    {
        $objectId = $this->object_id;

        if ( ! $objectId && $this->field instanceof Meta && 'post' === $this->field->object_type) {
            $objectId = get_the_ID();
        }

        return $objectId;
    }

    public static function getDefaultArgs()
    {
        return [
            'description'        => '',
            'output_desc'        => true, // 'p', true (span), false
            'before_desc'        => '',   // 'br', 'spacer'.
            'tooltip'            => false,
            'label'              => '',
            'label_for'          => '',
            'prefer_short_label' => false,
            'object_id'          => null,
            'context'            => null,
            'th_class'           => '',
            'th_style'           => '',
            'td_class'           => '',
            'td_style'           => 'vertical-align: middle;',
            'before'             => null,
            'after'              => null,
            'key_postfix'        => '',
            'getter'             => null,
        ];
    }
}
