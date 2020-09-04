<?php

namespace Naran\Axis\Func;

/**
 * Decompose hangul alphabet.
 *
 * @param $input
 *
 * @return array Decomposed hangul constants, and vowels.
 */
function hangulDecompose($input)
{
    $output = [];

    $hl = [
        'ㄱ',
        'ㄲ',
        'ㄴ',
        'ㄷ',
        'ㄸ',
        'ㄹ',
        'ㅁ',
        'ㅂ',
        'ㅃ',
        'ㅅ',
        'ㅆ',
        'ㅇ',
        'ㅈ',
        'ㅉ',
        'ㅊ',
        'ㅋ',
        'ㅌ',
        'ㅍ',
        'ㅎ',
    ];

    $vl = [
        'ㅏ',
        'ㅐ',
        'ㅑ',
        'ㅒ',
        'ㅓ',
        'ㅔ',
        'ㅕ',
        'ㅖ',
        'ㅗ',
        'ㅘ',
        'ㅙ',
        'ㅚ',
        'ㅛ',
        'ㅜ',
        'ㅝ',
        'ㅞ',
        'ㅟ',
        'ㅠ',
        'ㅡ',
        'ㅢ',
        'ㅣ',
    ];

    $tl = [
        '',
        'ㄱ',
        'ㄲ',
        'ㄳ',
        'ㄴ',
        'ㄵ',
        'ㄶ',
        'ㄷ',
        'ㄹ',
        'ㄺ',
        'ㄻ',
        'ㄼ',
        'ㄽ',
        'ㄾ',
        'ㄿ',
        'ㅀ',
        'ㅁ',
        'ㅂ',
        'ㅄ',
        'ㅅ',
        'ㅆ',
        'ㅇ',
        'ㅈ',
        'ㅊ',
        'ㅋ',
        'ㅌ',
        'ㅍ',
        'ㅎ',
    ];

    /**
     * polyfill-mbstring installed.
     */
    foreach (mb_str_split($input) as $chr) {
        $code = mb_ord($chr);
        if (44032 <= $code && $code <= 55203) {
            $t        = $code - 44032;
            $hi       = (int)($t / 588);
            $vi       = (int)(($t % 588) / 28);
            $ti       = (int)($t % 28);
            $output[] = [$hl[$hi], $vl[$vi], $tl[$ti]];
        } elseif (12593 <= $code && $code <= 12643) {
            $output[] = [$chr, '', ''];
        } else {
            $output[] = $chr;
        }
    }

    return $output;
}


/**
 * Determine proper postposition (조사).
 *
 * @param string $input Input string.
 * @param string $a     Postposition when last character endw with final consonant.
 * @param string $b     Postposition when last character ends with vowel.
 *
 * @return string
 */
function hangulPostposition($input, $a, $b)
{
    $output = '';
    $split  = mb_str_split($input);
    $last   = $split[count($split) - 1];

    if (is_numeric($last)) {
        if (in_array($last, ['0', '1', '3', '6', '7', '8'])) {
            $output = $input . $a;
        } else {
            $output = $input . $b;
        }
    } elseif (preg_match('/[A-Z]/i', $last)) {
        if (in_array(strtolower($last), ['l', 'm', 'n', 'r'])) {
            $output = $input . $a;
        } else {
            $output = $input . $b;
        }
    } else {
        $s = hangulDecompose($last);
        if (3 === count($s[0])) {
            if ( ! $s[0][1] && ! $s[0][2]) {
                // 단모음 단자음.
                $code = mb_ord($last);
                if (12593 <= $code && $code <= 12622) {
                    // vowel.
                    $output = $input . $b;
                } elseif (12623 <= $code && $code <= 12643) {
                    // consonant.
                    $output = $input . $a;
                }
            } elseif ($s[0][1] && ! $s[0][2]) {
                // non-final consonant.
                $output = $input . $b;
            } else {
                // final consonant.
                $output = $input . $a;
            }
        } else {
            // whitespace or unknown case....
            $output = $input;
        }
    }

    return $output;
}


/**
 * Postposition: 을/를
 *
 * @param string $input
 *
 * @return string
 */
function eulLul(string $input): string
{
    return hangulPostposition($input, '을', '를');
}


/**
 * Postposition: 이/가
 *
 * @param string $input
 *
 * @return string
 */
function yiGa(string $input): string
{
    return hangulPostposition($input, '이', '가');
}


/**
 * Postposition: 은/는
 *
 * @param $input
 *
 * @return string
 */
function eunNun($input)
{
    return hangulPostposition($input, '은', '는');
}


/**
 * Postposition: 과/와
 *
 * @param $input
 *
 * @return string
 */
function gwaWa($input)
{
    return hangulPostposition($input, '과', '와');
}


/**
 * Postposition: 으로/로
 *
 * @param $input
 *
 * @return string
 */
function euroRo($input)
{
    return hangulPostposition($input, '으로', '로');
}
