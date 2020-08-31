<?php
/**
 * Context:
 *
 * @var string        $content_header
 * @var string        $content_footer
 *
 * @var string        $table_header
 * @var string        $table_footer
 *
 * @var string        $nonce_arg
 * @var string        $nonce_action
 *
 * @var array         $table_attrs
 *
 * @var FieldWidget[] $widgets
 */


use Naran\Axis\View\Admin\FieldWidgets\FieldWidget;
use function Naran\Axis\Func\formatAttrs;

?>

<?php echo $content_header; ?>

    <table <?php echo formatAttrs($table_attrs); ?>>

        <?php echo $table_header; ?>

        <?php
        foreach ($widgets as $widget) {
            $widget->renderTr();
        }
        ?>

        <?php echo $table_footer; ?>
    </table>

<?php wp_nonce_field($nonce_action, $nonce_arg); ?>

<?php echo $content_footer;
