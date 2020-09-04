<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Shoplic\Axis3\Tests
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

/** @noinspection PhpIncludeInspection */
require_once $_tests_dir . '/includes/functions.php';

/** Manually load the plugin being tested. */
function _manually_load_plugin()
{
    require dirname(dirname(__FILE__)) . '/naran-axis.php';
}

/** @uses _manually_load_plugin() */
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
/** @noinspection PhpIncludeInspection */
require $_tests_dir . '/includes/bootstrap.php';

