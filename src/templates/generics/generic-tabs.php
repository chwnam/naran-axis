<?php
/**
 * Context:
 *
 * @var array $tabs [
 *                    [
 *                      'class' => 'nav-tab nav-tab-active',
 *                      'url'   => 'https://.....',
 *                      'label' => 'The Label',
 *                    ],
 *                    [ 'class' => ... ],
 *                    ...
 *                  ]
 *
 * @link https://make.wordpress.org/core/2019/04/02/admin-tabs-semantic-improvements-in-5-2/
 *
 * <nav class="nav-tab-wrapper wp-clearfix" aria-label="Secondary menu">
 *   <a href="about.php" class="nav-tab nav-tab-active">What’s New</a>
 *   <a href="credits.php" class="nav-tab">Credits</a>
 *   <a href="freedoms.php" class="nav-tab">Freedoms</a>
 *   <a href="freedoms.php?privacy-notice" class="nav-tab">Privacy</a>
 * </nav>
 */

use function Naran\Axis\Func\closeTag;
use function Naran\Axis\Func\openTag;

?>

<?php if (count($tabs) > 1) : ?>
    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e('Tabbed menu', 'axis3'); ?>">
        <?php foreach ($tabs as $tab) : ?>
            <?php
            openTag('a', ['class' => $tab['class'] ?? '', 'href' => $tab['url'] ?? '#']);
            echo esc_html($tab['label'] ?? '');
            closeTag('a');
            ?>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
