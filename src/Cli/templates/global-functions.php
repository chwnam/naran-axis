<?php

/**
 * @var string $namespace
 * @var string $slug
 */

echo '<?php' . PHP_EOL;
?>

/**
* Plugin global functions.
*
* @package <?= $namespace . PHP_EOL ?>
*/

namespace <?= $namespace ?>;

use \Naran\Axis\Starter\Starter;
use \Naran\Axis\Container\Container;


/**
 * Return starter of this plugin.
 *
 * @return Starter
 */
function getStarter()
{
    return axisGetStarter(<?= $slug ?>);
}


/**
 * Return container of this plugin.
 *
 * @return Container
 */
function getContainer()
{
    return axisGetContainer(<?= $slug ?>);
}


/**
* Return prefix.
*
* @param bool $preferDash Use dash or underscore.
*
* @return string
*/
function getPrefix($preferDash = false)
{
    return getStarter()->getPrefix($preferDash);
}


/**
* Return prefixed string.
*
* @param string $string     Input string.
* @param bool   $preferDash Use dash or underscore.
*
* @return string
*/
function prefixed($string, $preferDash = false)
{
    return getStarter()->prefixed($string, $preferDash);
}

