<?php


namespace Naran\Axis\Model\ValueType;


use Naran\Axis\Model\Field\VerificationFailedException;

/**
 * Class TextType
 *
 * @package Naran\Axis\Model\ValueType
 *
 * @property-read callable                   $sanitizer
 * @property-read bool                       $allow_blank
 * @property-read int|null                   $min_char
 * @property-read int|null                   $max_char
 * @property-read array<string, string>|null $choices
 * @property-read string|null                $regex
 */
class TextType extends ValueType
{
    /**
     * @inheritDoc
     */
    public function sanitize($value)
    {
        return call_user_func($this->sanitizer, $value);
    }

    /**
     * @inheritDoc
     */
    public function verify($value, $label)
    {
        $len = mb_strlen($value);

        if ((is_int($this->min_char) && $this->min_char > $len)) {
            throw new VerificationFailedException(
                sprintf(
                    _n(
                        'TextType verification failed. \'%s\' must be at least %d character.',
                        'TextType verification failed. \'%s\' must be at least %d characters.',
                        $this->min_char,
                        'naran-axis',
                    ),
                    $label,
                    $len
                )
            );
        } elseif (is_int($this->max_char) && $len > $this->max_char) {
            throw new VerificationFailedException(
                sprintf(
                    _n(
                        'TextType verification failed. \'%s\' must be at most %d character.',
                        'TextType verification failed. \'%s\' must be at most %d characters.',
                        $this->max_char,
                        'naran-axis',
                    ),
                    $label,
                    $len
                )
            );
        }

        if (is_array($this->choices)) {
            $choices = [];
            foreach ($this->choices as $k => $v) {
                if (is_array($v)) {
                    // $v is an array, intended for optgroup tags.
                    $choices = array_merge($choices, array_keys($v));
                } else {
                    $choices[] = $k;
                }
            }
            if ( ! in_array($value, $choices, false)) {
                throw new VerificationFailedException(
                    sprintf(
                        __('\'%s\' field value \'%s\' is not in the choices list.', 'naran-axis'),
                        $label,
                        $value
                    )
                );
            }
        }

        if ($this->regex && ! preg_match($this->regex, $value)) {
            throw new VerificationFailedException(
                sprintf(
                    __('\'%s\' field value \'%s\' does not match the regex pattern \'%s\'.'),
                    $label,
                    $value,
                    $this->regex
                )
            );
        }

        return $value;
    }

    public static function getDefaultArgs()
    {
        return array_merge(
            parent::getDefaultArgs(),
            [
                'sanitizer'   => 'sanitize_text_field',
                'allow_blank' => true,
                'min_char'    => null,
                'max_char'    => null,
                'choices'     => null,
                'regex'       => null,
            ],
        );
    }
}