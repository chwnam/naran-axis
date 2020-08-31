<?php

namespace Naran\Axis\Func;

/**
 * Enclose string.
 *
 * @param string $input
 * @param string $quoteChar
 *
 * @return string
 */
function encloseString($input, $quoteChar = '"')
{
    return "{$quoteChar}{$input}{$quoteChar}";
}


/**
 * Format HTML attributes.
 *
 * @param array $attributes
 *
 * @return string
 */
function formatAttrs($attributes)
{
    $buffer = [];

    foreach ($attributes as $key => $val) {
        $key = sanitize_key($key);

        /** @link https://html.spec.whatwg.org/multipage/indices.html#attributes-3 */
        switch ($key) {
            case 'accept':
                $func = function ($key, $value) {
                    if (is_string($value)) {
                        $value = array_filter(array_map('trim', explode(',', $value)));
                    }

                    return $key . '=' . encloseString(
                            implode(', ', array_unique(array_map('sanitize_mime_type', $value)))
                        );
                };
                break;

            case 'class':
                $func = function ($key, $value) {
                    if (is_string($value)) {
                        $value = preg_split('/\s+/', $value);
                    }

                    return $key . '=' . encloseString(implode(' ', array_map('sanitize_html_class', $value)));
                };
                break;

            case 'action':
            case 'cite':
            case 'data':
            case 'formaction':
            case 'href':
            case 'itemid':
            case 'itemprop':
            case 'itemtype':
            case 'manifest':
            case 'ping':
            case 'poster':
            case 'src':
                $func = function ($key, $value) {
                    return $key . '=' . encloseString(implode(' ', array_map('esc_url', preg_split('/\s+/', $value))));
                };
                break;

            case 'allowfullscreen':
            case 'allowpaymentrequest':
            case 'async':
            case 'autofocus':
            case 'autoplay':
            case 'checked':
            case 'controls':
            case 'default':
            case 'defer':
            case 'disabled':
            case 'formnovalidate':
            case 'hidden':
            case 'ismap':
            case 'itemscope':
            case 'loop':
            case 'multiple':
            case 'muted':
            case 'nomodule':
            case 'novalidate':
            case 'open':
            case 'playsinline':
            case 'readonly':
            case 'required':
            case 'reversed':
            case 'selected':
                /*
                 * Those attributes are written like:
                 * <input ... readonly>
                 * <input ... readonly="">
                 * <input ... readonly="readonly">
                 *
                 * And their value accepts boolean type:
                 * $attrs = [
                 *   'id'       => 'foo',
                 *   'name'     => 'foo',
                 *   'required' => true,
                 * ]
                 */
                $func = function ($key, $val) {
                    if ($key) {
                        if (is_bool($val)) {
                            return $val ? $key . '=' . encloseString($key) : '';
                        } else {
                            return $val ? $key . '=' . encloseString(esc_attr($val)) : $key;
                        }
                    }

                    return '';
                };
                break;

            case 'value':
                $func = function ($key, $val) {
                    // NOTE: string '0' must be echoed.
                    return $key . '=' . encloseString(esc_attr($val));
                };
                break;

            default:
                $func = function ($key, $val) {
                    if ($key) {
                        return $val ? $key . '=' . encloseString(esc_attr($val)) : $key;
                    }

                    return '';
                };
                break;
        }

        if ($key) {
            $buffer[] = call_user_func($func, $key, $val);
        }
    }

    return ' ' . implode(' ', $buffer);
}


/**
 * Open a tag.
 *
 * @param string $tag
 * @param array  $attributes
 * @param bool   $echo
 *
 * @return string|null
 */
function openTag($tag, $attributes = [], $echo = true)
{
    $output = '';
    $tag    = sanitize_key($tag);
    $attrs  = formatAttrs($attributes);

    if ($tag && $attrs) {
        $output = '<' . $tag . $attrs . '>';
    }

    if ($echo) {
        echo $output;

        return null;
    }

    return $output;
}


/**
 * Close a tag.
 *
 * @param string $tag
 * @param bool   $echo
 *
 * @return string|null
 */
function closeTag($tag, $echo = true)
{
    $output = '';
    $tag    = sanitize_key($tag);

    if ($tag) {
        $output = '</' . $tag . '>';
    }

    if ($echo) {
        echo $output;

        return null;
    }

    return $output;
}


/**
 * <input> tag.
 *
 * @param array $attributes
 * @param bool  $echo
 *
 * @return string|null
 */
function inputTag($attributes = [], $echo = true)
{
    return openTag('input', $attributes, $echo);
}


/**
 * <option> tag.
 *
 * @param string      $value      Value for 'value' attribute. <option value="{$value}" .... >
 * @param string      $label      Text node.
 * @param string|bool $selected   Selected value. 'selected' attribute is appended if $value == $selected, or true.
 * @param array       $attributes Tag attributes.
 * @param bool        $echo       Echo or return.
 *
 * @return string|null
 */
function optionTag(string $value, string $label, $selected, array $attributes = [], bool $echo = true)
{
    $attributes['value']    = $value;
    $attributes['selected'] = is_bool($selected) ? $selected : $value == $selected;

    $output = openTag('option', $attributes, false) . esc_html($label) . closeTag('option', false);

    if ($echo) {
        echo $output;

        return null;
    }

    return $output;
}


/**
 * <select> tag.
 *
 * @param array              $options          Key - value pairs for options.
 *                                             if value is an another array, and key is option group's label, and value
 *                                             array is options of the option group.
 * @param string|array       $selected         Selected value.
 * @param array              $attributes       <select> attributes.
 * @param array              $optionAttributes <option> attributes.
 *                                             key is option tag's value.
 * @param array|string|false $headingOption    Append heading disabled option.
 *                                             - false: do not use.
 *                                             - array: length must be length 2. 0th: value, 1st: label.
 *                                             - string: label. value is empty.
 *                                             Sample:
 *                                             <option value="" disabled="disabled">레이블</option>
 * @param bool               $echo
 *
 * @return string|null
 *
 * @example
 * option 태그만 사용하는 예:
 * $options = [
 *   'volvo'    => 'Volvo',        // <option value="volvo">Volvo</option>
 *   'saab'     => 'Saab',         // <option value="saab">Saab</option>
 *   'mercedes' => 'Mercedes',     // <option value="mercedes">Mercedes</option>
 *   'audi'     => 'Audi',         // <option value="audi">Audi</option>
 * ]
 *
 * optgroup 태그와 혼용하는 예:
 * $options = [
 *   'Swedish Cars' => [             // <optgroup label="Swedish Cars">
 *     'volvo'    => 'Volvo',        //   <option value="volvo">Volvo</option>
 *     'saab'     => 'Saab',         //   <option value="saab">Saab</option>
 *   ],                              // </optgroup>
 *   'German Cars' => [              // <optgroup label="German Cars">
 *     'mercedes' => 'Mercedes',     //   <option value="mercedes">Mercedes</option>
 *     'audi'     => 'Audi',         //   <option value="audi">Audi</option>
 *   ],                              // </optgroup>
 * ]
 *
 * <option class="mercedes-option" data-type="car-brand"> ... 처럼 옵션 태그에 속성 추가 예:
 * $optionAttributes = [
 *   'mercedes' => [
 *     'class'      => 'mercedes-option',
 *     'data-type'  => 'car-brand',
 *   ]
 * ]
 */
function selectTag(
    $options = [],
    $selected = '',
    $attributes = [],
    $optionAttributes = [],
    $headingOption = false,
    $echo = true
) {
    $buffer = [openTag('select', $attributes, false)];

    if (is_array($selected)) {
        $selected = array_combine(array_values($selected), array_pad([], count($selected), true));
    }

    if (is_array($headingOption) && sizeof($headingOption) >= 2) {
        $buffer[] = optionTag(
            $headingOption[0],
            $headingOption[1],
            $selected,
            [
                'disabled' => true,
                'selected' => isset($selected[$headingOption[0]]),
            ],
            false
        );
    } elseif (is_string($headingOption)) {
        $buffer[] = optionTag(
            '',
            $headingOption,
            $selected,
            [
                'disabled' => true,
                'selected' => empty($selected),
            ],
            false
        );
    }

    foreach ($options as $value => $item) {
        if (is_array($item)) {
            $buffer[] = openTag('optgroup', array_merge(['label' => $value], $optionAttributes[$value] ?? []), false);
            foreach ($item as $val => $label) {
                $buffer[] = optionTag(
                    $val,
                    $label,
                    is_array($selected) ? isset($selected[$val]) : $selected,
                    $optionAttributes[$val] ?? [],
                    false
                );
            }
            $buffer[] = closeTag('optgroup');
        } else {
            $buffer[] = optionTag(
                $value,
                $item,
                is_array($selected) ? isset($selected[$value]) : $selected,
                $optionAttributes[$value] ?? [],
                false
            );
        }
    }

    $buffer[] = closeTag('select', false);

    if ($echo) {
        echo implode("\n", $buffer);

        return null;
    }

    return implode("\n", $buffer);
}


/**
 * Simple <ul>, <ol> tag.
 *
 * @param string $tag            Wrapping tag. ol, ul.
 * @param array  $attributes     Tag attributes.
 * @param string $itemTag        List tag. defaults to 'li'.
 * @param array  $items          Items.
 * @param array  $listAttributes Key: index (key) of item, value: array.
 * @param bool   $escape         Escape <li> child nodes or not.
 * @param bool   $echo           Output or return.
 *
 * @return string|null
 */
function listTag(
    $tag,
    $attributes = [],
    $itemTag = 'li',
    $items = [],
    $listAttributes = [],
    $escape = true,
    $echo = true
) {
    if ( ! $echo) {
        ob_start();
    }

    openTag($tag, $attributes);
    foreach ($items as $idx => $item) {
        openTag($itemTag, $listAttributes[$idx] ?? []);
        echo $escape ? esc_html($item) : $item;
        closeTag($itemTag);
    }
    closeTag($tag);

    if ( ! $escape) {
        return ob_get_clean();
    }

    return null;
}
