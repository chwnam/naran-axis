<?php

namespace Naran\Axis\Cli;

use WP_CLI;

use function Naran\Axis\Func\strStartsWith;

/**
 * Axi CLI commands.
 *
 * @package Naran\Axis\Cli
 */
class CliHandler
{
    /**
     * Initialize a new plugin.
     *
     * <plugin_dir>
     * : Plugin's root directory name. A directory with this value is created.
     *
     * <plugin_namespace>
     * : Plugin's root namespace.
     *
     * [--plugin_name=<name>]
     * : Plugin's name.
     *
     * [--description=<description>]
     * : Plugin's description header content.
     *
     * [--version=<version>]
     * : Plugin version.
     *
     * [--author=<author>]
     * : 'Author' header content.
     *
     * [--author_uri=<author_uri>]
     * : 'Author URI' header content.
     *
     * [--plugin_uri=<plugin_uri>]
     * : 'Plugin URI' header content.
     *
     * [--license=<license>]
     * : 'License' header content.
     *
     * [--textdomain=<textdomain>]
     * : 'Textdomain' header content.
     *
     * [--composer=<composer_path>]
     * : Path to composer script.
     *
     * @param array $args
     * @param array $kwargs
     *
     * @throws WP_CLI\ExitException
     */
    public function init_plugin($args, $kwargs)
    {
        $directory  = sanitize_key(array_shift($args));
        $namespace  = rtrim(array_shift($args), '\\') . '\\';
        $pluginPath = WP_PLUGIN_DIR . '/' . $directory;
        $slug       = str_replace('-', '_', trim($directory, '-_'));
        $region     = 'Ground';

        if (file_exists($pluginPath)) {
            WP_CLI::error("Plugin '{$directory}' already exists. Please choose another plugin directory.");
        } elseif (empty($directory)) {
            WP_CLI::error('<plugin-name> is invalid. Please choose another plugin directory.');
        }

        $default = [
            'plugin_name' => '',
            'description' => '',
            'version'     => '',
            'author'      => '',
            'author_uri'  => '',
            'plugin_uri'  => '',
            'license'     => '',
            'textdomain'  => '',
        ];

        $kwargs = wp_parse_args($kwargs, $default);

        mkdir($pluginPath, 0755);

        // create main file with headers.
        new PhpTemplate(
            'plugin-main.php',
            array_merge(
                $kwargs,
                [
                    'namespace' => $namespace,
                    'slug'      => $slug,
                ]
            ),
            "{$pluginPath}/{$directory}.php"
        );

        // create composer.json
        $ns = explode('\\', trim($namespace, '\\'));
        if (2 === count($ns)) {
            $composerName = strtolower($ns[0] . '/' . $ns[1]);
        } else {
            $composerName = $directory;
        }

        $composer = [
            'name'        => $composerName,
            'description' => $kwargs['description'],
            'type'        => 'wordpress-plugin',
            'license'     => $kwargs['license'],
            'authors'     => [],
            'autoload'    => [
                'psr-4' => [$namespace => 'src/'],
                'files' => ["src/{$region}/Func/Global.php"],
            ],
        ];

        if ($kwargs['author']) {
            $composer['authors'][] = [
                'name'     => $kwargs['author'],
                'email'    => strStartsWith($kwargs['author_uri'], 'mailto://') ? $kwargs['author_uri'] : '',
                'homepage' => strStartsWith($kwargs['author_uri'], 'http://') ||
                              strStartsWith($kwargs['author_uri'], 'https://') ? $kwargs['author_uri'] : '',
                'role'     => '',
            ];
        }

        if (strStartsWith($kwargs['plugin_uri'], 'http://') || strStartsWith($kwargs['plugin_uri'], 'https://')) {
            $composer['homepage'] = $kwargs['plugin_uri'];
        }

        new JsonTemplate($composer, $pluginPath . '/composer.json');

        mkdir("{$pluginPath}/src/{$region}/Initiator", 0755, true);
        mkdir("{$pluginPath}/src/{$region}/Func", 0755, true);
        mkdir("{$pluginPath}/src/assets/css", 0755, true);
        mkdir("{$pluginPath}/src/assets/img", 0755, true);
        mkdir("{$pluginPath}/src/assets/js", 0755, true);
        mkdir("{$pluginPath}/src/templates", 0755, true);

        new PhpTemplate(
            'global-functions.php',
            [
                'namespace' => $namespace . "{$region}\\Func",
                'slug'      => strtoupper($slug) . '_SLUG',
            ],
            "{$pluginPath}/src/{$region}/Func/Global.php"
        );

        new PhpTemplate(
            'sample-initiator.php',
            [
                'rootNamespace' => $namespace,
                'namespace'     => $namespace . "{$region}\\Initiator",
                'pluginName'    => $kwargs['plugin_name'],
            ],
            "{$pluginPath}/src/{$region}/Initiator/SampleInitiator.php"
        );

        if ( ! isset($kwargs['composer'])) {
            $output = shell_exec('which composer');
            if ($output) {
                $composer = trim($output);
            }
        } else {
            $composer = $kwargs['composer'];
        }

        if (empty($composer)) {
            WP_CLI::error('composer not found.');
        }

        shell_exec("{$composer} dump-autoload -d {$pluginPath}");

        WP_CLI::success("Plugin successfully initialized.");
    }

    /**
     * Add a new region to plugin.
     * e.g. wp axis add_region test foo
     * add a new region 'foo' to plugin 'test' (wp-content/plugins/test).
     *
     * <plugin_dir>
     * : Plugin's root directory name.
     *
     * <region>
     * : Region name to add.
     *
     * @param $args
     *
     * @throws WP_CLI\ExitException
     */
    public function add_region($args)
    {
        $plugin = array_shift($args);
        $region = ucfirst(array_shift($args));

        if ( ! $this->includePlugin($plugin)) {
            WP_CLI::error("Plugin directory {$plugin} does not exist.");
        }

        $starter = AxisGetStarter($plugin);
        if ( ! $starter) {
            WP_CLI::error("Plugin's main file loading is failed.");
        }

        $srcPath    = $starter->getSrcPath();
        $regionPath = $srcPath . '/' . $region;
        if (file_exists($regionPath)) {
            WP_CLI::error("Region {$region} already exists.");
        }

        mkdir($regionPath, 0755);
        mkdir("$regionPath/Initiator", 0755);
        mkdir("$regionPath/Model", 0755);
        mkdir("$regionPath/View", 0755);

        WP_CLI::success("Region '{$region}' is successfully created under the '{$plugin}' plugin.");
    }

    /**
     * Add a Initiator class
     *
     * <plugin_dir>
     * : Plugin's root directory name.
     *
     * <region>
     * : Plugin's region.
     *
     * <initiator>
     * : New initiator class name.
     *
     * [--base_initiator]
     * : Add BaseInitiator instead of AutoHookInitiator.
     *
     * @param $args
     * @param $kwargs
     *
     * @throws WP_CLI\ExitException
     */
    public function add_initiator($args, $kwargs)
    {
        $plugin  = array_shift($args);
        $region  = ucfirst(array_shift($args));
        $class   = ucfirst(array_shift($args));
        $useBase = isset($kwargs['base_initiator']);

        if ( ! $this->includePlugin($plugin)) {
            WP_CLI::error("Plugin directory {$plugin} does not exist.");
        }

        $starter = AxisGetStarter($plugin);
        if ( ! $starter) {
            WP_CLI::error("Plugin's main file loading is failed.");
        }

        $srcPath    = $starter->getSrcPath();
        $regionPath = $srcPath . '/' . $region;
        if ( ! file_exists($regionPath)) {
            WP_CLI::error("Region {$region} not found.");
        }

        $classPath = $regionPath . '/Initiator/' . $class . '.php';
        if (file_exists($classPath)) {
            WP_CLI::error("Class '{$class}' already exists.");
        }

        new PhpTemplate(
            'new-initiator.php',
            [
                'namespace' => $starter->getNamespace() . $region . '\\Initiator',
                'className' => $class,
                'useBase'   => $useBase,
            ],
            $classPath
        );

        WP_CLI::success("Class '{$class}' is successfully created under the region '{$region}'.");
    }

    private function includePlugin($pluginDir)
    {
        foreach (array_keys(get_plugins()) as $plugin) {
            $exploded = explode('/', $plugin, 2);
            if ($exploded && $pluginDir === $exploded[0]) {
                include WP_PLUGIN_DIR . "/{$plugin}";

                return true;
            }
        }

        return false;
    }
}
