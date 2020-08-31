<?php
/**
 * Context
 *
 * @var string $option_group
 * @var string $button_text
 */

if ( ! isset($button_text)) {
    $button_text = null;
}
?>

<form method="post" action="<?php echo esc_url(admin_url('options.php')) ?>">

    <?php settings_fields($option_group); ?>

    <?php do_settings_sections($option_group); ?>

    <?php submit_button($button_text); ?>

</form>
