<?php
/**
 * Plugin Name: Naran Axis
 * Description: A WordPress MU (Must-Use) plugin for developing highly customized websites.
 * Author:      Changwoo
 * Author URI:  https://blog.changwoo.pe.kr
 * Plugin URI:  https://github.com/chwnam/naran-axis
 * Version:
 * License:     GPL-2.0+
 * Text Domain: naran-axis
 *
 * @author    Changwoo Nam
 * @copyright 2020 Changwoo Nam
 * @license   GPL-2.0+
 */

require __DIR__ . '/vendor/autoload.php';

use Naran\Axis\Cli\CliHandler;

define('AXIS_MAIN', __FILE__);
define('AXIS_VERSION', '');

function naran_axis_load_language()
{
    load_plugin_textdomain('naran-axis', false, wp_basename(__DIR__) . '/languages');
}

add_action('mu_plugins_loaded', 'naran_axis_load_language');


function naran_axis_register_scripts()
{
    $asset = plugin_dir_url(AXIS_MAIN) . 'src/asset';
    $js    = "{$asset}/js/";
    $css   = "{$asset}/css/";

    wp_register_script(
        'axis-field-widget',
        "{$js}admin/field-widget/field-widget.js",
        ['jquery'],
        AXIS_VERSION,
        true
    );

    wp_register_style(
        'axis-field-widget',
        "{$css}admin/field-widget/field-widget.css",
        [],
        AXIS_VERSION
    );
}

add_action('admin_enqueue_scripts', 'naran_axis_register_scripts');


if (defined('WP_CLI') && WP_CLI && class_exists('WP_CLI')) {
    try {
        WP_CLI::add_command('axis', CliHandler::class);
    } catch (Exception $e) {
        echo "WP CLI error: {$e->getMessage()}";
    }
}

require_once __DIR__ . '/watch/watch.php';
