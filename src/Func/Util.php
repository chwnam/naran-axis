<?php

namespace Naran\Axis\Func;


/**
 * Check if haystack is starts with needle string.
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function strStartsWith(string $haystack, string $needle): bool
{
    return $needle === '' || strpos($haystack, $needle) === 0;
}


/**
 * Check if haystack is ends with needle string.
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function strEndsWith(string $haystack, string $needle): bool
{
    return
        $needle === '' ||
        (
            (($h = strlen($haystack)) >= ($n = strlen($needle))) &&
            substr($haystack, $h - $n) === $needle
        );
}


/**
 * @sample thisIsASnakeCasedSentence ==> this_is_a_snake_cased_sentence
 *
 * @param string $string
 * @param string $glue
 *
 * @return string
 */
function toSnakeCase(string $string, string $glue = '_'): string
{
    return strtolower(preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z])/', $glue, $string));
}


/**
 * @sample this_is_a_pascal_cased_sentence ==> ThisIsAPascalCasedSentence
 *
 * @param string $string
 * @param string $glue
 *
 * @return string
 */
function toPascalCase(string $string, string $glue = '_'): string
{
    return str_replace($glue, '', ucwords($string, $glue));
}


/**
 * @sample this_is_a_camel_cased_sentence ==> thisIsACamelCasedSentence
 *
 * @param string $string
 * @param string $glue
 *
 * @return string
 */
function toCamelCase(string $string, string $glue = '_'): string
{
    return lcfirst(toPascalCase($string, $glue));
}


/**
 * Check type of current request.
 *
 * @param $request
 *
 * @return bool
 */
function isRequest($request)
{
    switch ($request) {
        case 'AdminAjax':
            // Only admin-ajax.
            return wp_doing_ajax();

        case 'AdminPost':
            // Only admin-post.
            return is_admin() && strEndsWith($_SERVER['REQUEST_URI'] ?? false, '/wp-admin/admin-post.php');

        case 'Admin':
            // Administration screen, admin-post.php, and admin-ajax.php
            return is_admin();

        case 'Autosave':
            return defined('DOING_AUTOSAVE') && DOING_AUTOSAVE;

        case 'Cli':
            return defined('WP_CLI') && WP_CLI;

        case 'Cron':
            return wp_doing_cron();

        case 'Front':
            return ! is_admin();

        case 'FrontNoAjax':
            return ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron());

        case 'Repairing':
            return defined('WP_REPAIRING') && WP_REPAIRING;

        case 'RestRequest':
            return defined('REST_REQUEST') && REST_REQUEST;

        default:
            return apply_filters('naran_axis_is_request', true);
    }
}


/**
 * Get path part from given URL.
 *
 * @param string|null $url null for current request uri.
 *
 * @return false|string
 */
function getUrlPath($url = null)
{
    if ( ! $url) {
        $url = $_SERVER['REQUEST_URI'] ?? '';
    }

    return parse_url($url, PHP_URL_PATH);
}
