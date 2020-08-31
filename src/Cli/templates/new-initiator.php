<?php
/**
 * @var string $namespace
 * @var string $className
 * @var bool   $useBase
 */

echo '<?php' . PHP_EOL;
?>

namespace <?php echo $namespace; ?>;

<?php if($useBase) : ?>
use Naran\Axis\Initiator\BaseInitiator;
<?php else : ?>
use Naran\Axis\Initiator\AutoHookInitiator;
<?php endif; ?>

class <?php echo $className; ?> extends <?php echo ($useBase ? 'BaseInitiator' : 'AutoHookInitiator') .PHP_EOL; ?>
{
<?php echo $useBase ? "    public function initHooks()\n    {\n    }\n" : ''; ?>
}
