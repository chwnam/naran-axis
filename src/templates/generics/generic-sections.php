<?php
/**
 * Context:
 *
 * @var array $sections [
 *                        [
 *                          'class' => 'current',
 *                          'url'   => '#',
 *                          'label' => 'The Label',
 *                        ],
 *                        [ 'class' => ... ],
 *                        ...
 *                      ]
 *
 *
 * <ul class="subsubsub">
 *   <li><a href="#" class="current">The Label</a>|</li>
 *   <li><a href="#" class="">The Other Label</a>|</li>
 * </ul>
 * <br class="clear">
 */
$total_count = count($sections);
?>
<?php if ($total_count > 1) : ?>
    <ul class="subsubsub">
        <?php foreach ($sections as $idx => $section) : ?>
            <li>
                <a href="<?php echo esc_url($section['url'] ?? ''); ?>"
                   class="<?php echo esc_attr($section['class'] ?? ''); ?>"
                ><?php echo esc_html($section['label'] ?? ''); ?></a>
                <?php echo ($idx + 1 < $total_count) ? '|' : ''; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <br class="clear"/>
<?php endif; ?>
