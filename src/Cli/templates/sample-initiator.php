<?php
/**
 * @var string $rootNamespace
 * @var string $namespace
 * @var string $pluginName
 */

echo '<?php' . PHP_EOL;
?>

namespace <?php echo $namespace . ';' . PHP_EOL; ?>

use Naran\Axis\Initiator\AutoHookInitiator;
use function <?php echo $rootNamespace . 'Ground\\Func\\getStarter;' .PHP_EOL; ?>

class SampleInitiator extends AutoHookInitiator
{
    public function action_admin_notices()
    {
        echo '<div class="notice notice-success"><p>';
        echo 'Plugin \'<?= addslashes($pluginName); ?>\'is successfully running!<br>';
        echo 'Main file path: ' . <?= 'esc_html(getStarter()->getMainFile())' ?>;
        echo '</p></div>';
    }
}
