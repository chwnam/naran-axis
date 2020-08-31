<?php

/**
 * Context:
 *
 * @var string $plugin_name
 * @var string $description
 * @var string $author
 * @var string $author_uri
 * @var string $plugin_uri
 * @var string $version
 * @var string $license
 * @var string $textdomain
 *
 * @var string $namespace
 * @var string $slug
 */
echo '<?php' . PHP_EOL;
?>

/**
 * Plugin Name: <?= $plugin_name . PHP_EOL ?>
 * Description: <?= $description . PHP_EOL ?>
 * Author:      <?= $author . PHP_EOL ?>
 * Author URI:  <?= $author_uri . PHP_EOL ?>
 * Plugin URI:  <?= $plugin_uri . PHP_EOL ?>
 * Version:     <?= $version . PHP_EOL ?>
 * License:     <?= $license . PHP_EOL ?>
 * Textdomain:  <?= $textdomain . PHP_EOL ?>
 */

define('<?php echo strtoupper($slug); ?>_MAIN', __FILE__);
define('<?php echo strtoupper($slug); ?>_VERSION', '<?= $version ?>');
define('<?php echo strtoupper($slug); ?>_SLUG', '<?= $slug ?>');

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong><?= addslashes($plugin_name) ?></strong> plugin requires vendor/autoload.php. Please run `<code>composer dump-autoload -a</code>` to work properly.';
        echo '</p></div>';
    });
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

axisStart(
    [
        'main_file' => <?php echo strtoupper($slug); ?>_MAIN,
        'version'   => <?php echo strtoupper($slug); ?>_VERSION,
        'namespace' => '<?= addslashes($namespace) ?>',
    ]
);
