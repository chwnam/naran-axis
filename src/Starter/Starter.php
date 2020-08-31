<?php


namespace Naran\Axis\Starter;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewServiceProvider;
use Naran\Axis\Container\Container;
use Naran\Axis\Model\Connection;
use Naran\Axis\Repository\Repository;
use Naran\Axis\Starter\ClassFinder\AutoDiscoverClassFinder;
use Naran\Axis\Starter\ClassFinder\ClassFinder;
use Naran\Axis\Starter\ClassResolver\AllGrantedRegionFilter;
use Naran\Axis\Starter\ClassResolver\ClassResolver;
use Naran\Axis\Starter\ClassResolver\ContextFilter;
use Naran\Axis\Starter\ClassResolver\InitiatorClassResolver;
use Naran\Axis\Starter\ClassResolver\ModelClassResolver;
use Naran\Axis\Starter\ClassResolver\RegionFilter;
use Naran\Axis\Starter\ClassResolver\RequestContextContextFilter;


/**
 * Class Starter
 *
 * @package Naran\Axis\Starter
 */
class Starter
{
    /**
     * This starter's container.
     *
     * @var Container
     */
    private $container;

    /**
     * @param array $args
     *
     * @return static
     * @throws StarterFailureException
     */
    public static function factory($args = [])
    {
        $args = wp_parse_args(
            $args,
            [
                /**
                 * Plugin's main file. Required.
                 *
                 * @var string
                 */
                'main_file'        => '',

                /**
                 * Unique identifier of the plugin. Defaults to directory of the plugin, or basename of $main_file.
                 *
                 * @var string
                 */
                'slug'            => '',

                /**
                 * Plugin version. Defaults to empty string.
                 *
                 * @var string
                 */
                'version'         => '',

                /**
                 * Plugin base namespace. Defaults to empty string.
                 *
                 * To properly discover all classes and instantiate required components, this value should be set.
                 *
                 * @var string
                 */
                'namespace'       => '',

                /**
                 * Plugin's sub-directory where its namespace is mapped. Defaults to `dirname($main_file)/src`.
                 *
                 * @var null|string
                 */
                'src'         => null,

                /**
                 * Textdomain string. Assign this value to load plugin's translation file automatically.
                 *
                 * @var string
                 */
                'textdomain'      => '',

                /**
                 * Prefix string without trailing underscore or hyphen. Defaults to $slug.
                 *
                 * @var string
                 */
                'prefix'      => '',

                /**
                 * For multisite. Plugin only starts when the current blog id is matched. Defaults to null.
                 *
                 * @var int|int[]|callable|null
                 */
                'blog_id'          => null,

                /**
                 * Assign a region filter. Defaults to null, or AllGrantedRegionFilter.
                 *
                 * @var null|RegionFilter
                 */
                'region_filter'    => null,

                /**
                 * Assign a context filter. Defaults to null, or RequestContextContextFilter.
                 *
                 * @var null|ContextFilter
                 */
                'context_filter'   => null,

                /**
                 * Assign a class finder. Defaults to null, AutoDiscoverClassFinder.
                 *
                 * @var null|ClassFinder
                 */
                'class_finder'     => null,

                /**
                 * Assign class resolvers. Defaults to null.
                 *
                 * @var null|ClassResolver[]
                 */
                'class_resolvers'  => null,

                /**
                 * Callback. Invoked before start() method is called.
                 *
                 * @var null|callable
                 */
                'before_start'     => null,

                /**
                 * The plugin's default action, filter priority. Defaults to 10.
                 *
                 * @var int
                 */
                'default_priority' => 10,

                /**
                 * Use Blade template engine.
                 *
                 * @var bool
                 */
                'use_blade'        => true,

                /**
                 * Use Eloquent ORM.
                 *
                 * @var bool
                 */
                'use_eloquent'     => true,
            ]
        );

        $starter            = new static();
        $starter->container = new Container();
        $starter->container->instance(static::class, $starter);
        $starter->container->alias(static::class, 'starter');

        // Setup main_file
        if ($args['main_file']) {
            $starter->container->instance('starter.main_file', $args['main_file']);
        } else {
            throw new StarterFailureException(
                __('Argument \'main_file\' is required.', 'naran-axis')
            );
        }

        // Setup slug.
        $args['slug'] = sanitize_key($args['slug']);
        if ($args['slug']) {
            $starter->container->instance('starter.slug', $args['slug']);
        } else {
            $dir = dirname($args['main_file']);
            if (WP_PLUGIN_DIR === $dir) {
                // The plugin is a single file so that slug is named after its file name.
                $starter->container->instance('starter.slug', pathinfo($starter->getMainFile(), PATHINFO_FILENAME));
            } else {
                // The plugin is under a directory so that slug is named after its directory name.
                $starter->container->instance('starter.slug', wp_basename($dir));
            }
        }

        // Setup version.
        if (is_string($args['version']) && ! empty($args['version'])) {
            $starter->container->instance('starter.version', $args['version']);
        }

        // Setup namespace.
        if (is_string($args['namespace']) && ! empty($args['namespace'])) {
            $starter->container->instance('starter.namespace', trim($args['namespace'], '\\') . '\\');
        }

        // Setup src.
        if ($args['src']) {
            $starter->container->instance('starter.src', untrailingslashit($args['src']));
        } else {
            $starter->container->instance('starter.src', dirname($starter->getMainFile()) . '/src');
        }

        // Setup prefix.
        $args['prefix'] = rtrim(sanitize_key($args['prefix']), '-_');
        if ($args['prefix']) {
            $starter->container->instance('starter.prefix', $args['prefix']);
        } else {
            $starter->container->instance('starter.prefix', rtrim($starter->getSlug(), '-_'));
        }

        // Setup textdomain.
        $args['textdomain'] = sanitize_key($args['textdomain']);
        if ($args['textdomain']) {
            $starter->container->instance('starter.textdomain', $args['textdomain']);
        } else {
            $starter->container->instance('starter.textdomain', '');
        }

        // Setup blog_id
        if (is_numeric($args['blog_id']) || (is_array($args['blog_id']) && ! is_callable($args['blog_id']))) {
            $starter->container->instance('starter.blog_id', array_filter(array_map('intval', (array)$args['blog_id'])));
        } elseif (is_callable($args['blog_id'])) {
            $starter->container->instance('starter.blog_id', $args['blog_id']);
        } else {
            $starter->container->instance('starter.blog_id', null);
        }

        // Setup region_filter
        if ($args['region_filter']) {
            $starter->container->bindIf(RegionFilter::class, $args['region_filter']);
        } else {
            $starter->container->bindIf(RegionFilter::class, AllGrantedRegionFilter::class);
        }

        // Setup context_filter
        if ($args['context_filter']) {
            $starter->container->bindIf(ContextFilter::class, $args['context_filter']);
        } else {
            $starter->container->bindIf(ContextFilter::class, RequestContextContextFilter::class);
        }

        // Setup class_finder
        if ($args['class_finder']) {
            $starter->container->bindIf(ClassFinder::class, $args['class_finder']);
        } else {
            $starter->container->bindIf(
                ClassFinder::class,
                function () use ($starter) {
                    $components    = ['Initiator', 'Model'];
                    $rootNamespace = $starter->getNamespace();
                    $rootPath      = $starter->getSrcPath();

                    return new AutoDiscoverClassFinder($components, $rootNamespace, $rootPath);
                }
            );
        }

        // Setup resolvers
        if ($args['class_resolvers']) {
            $starter->container->bindIf('class_resolvers', $args['class_resolvers']);
        } else {
            $starter->container->bindIf(
                'class_resolvers',
                function ($app) {
                    /** @var Container $app */
                    $starter       = $app->make(Starter::class);
                    $finder        = $app->make(ClassFinder::class);
                    $regionFilter  = $app->make(RegionFilter::class);
                    $contextFilter = $app->make(ContextFilter::class);

                    return [
                        new InitiatorClassResolver($starter, $finder, $regionFilter, $contextFilter),
                        new ModelClassResolver($starter, $finder, $regionFilter)
                    ];
                }
            );
        }

        // Default priority.
        $starter->container->instance('starter.default_priority', intval($args['default_priority']));

        // Blade template.
        $starter->container->instance('starter.use_blade', boolval($args['use_blade']));

        // Eloquent ORM.
        $starter->container->instance('starter.use_eloquent', boolval($args['use_eloquent']));

        // End of configuration!
        StarterPool::getInstance()->addStarter($starter);

        if (is_callable($args['before_start'])) {
            call_user_func($args['before_start'], $starter->container, $starter);
        }

        return $starter;
    }

    /**
     * @throws BindingResolutionException|StarterFailureException
     */
    public function start()
    {
        if ($this->isAvailable()) {
            $this->prepareBlade();
            $this->prepareEloquent();

            foreach ($this->getContainer()->make('class_resolvers') as $resolver) {
                /** @var ClassResolver $resolver */
                $resolver->resolve();
            }
            if ($this->getTextdomain()) {
                add_action('plugins_loaded', [$this, 'loadTextdomain']);
            }
        }
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @callback
     * @action      plugins_loaded
     *
     * @used-by     start()
     */
    public function loadTextdomain()
    {
        load_plugin_textdomain(
            $this->getTextdomain(),
            false,
            wp_basename(dirname($this->getMainFile())) . '/languages'
        );
    }

    /**
     * Return plugin main file.
     *
     * @return string
     */
    public function getMainFile()
    {
        return $this->container['starter.main_file'];
    }

    /**
     * Return plugin version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->container['starter.version'];
    }

    /**
     * Return plugin root namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->container['starter.namespace'];
    }

    /**
     * Return plugin src path.
     *
     * @return string
     */
    public function getSrcPath()
    {
        return $this->container['starter.src'];
    }

    /**
     * Return plugin slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->container['starter.slug'];
    }

    /**
     * Return plugin textdomain.
     *
     * @return string
     */
    public function getTextdomain()
    {
        return $this->container['starter.textdomain'];
    }

    /**
     * Return prefix.
     *
     * @param bool $preferDash Use dash or underscore.
     *
     * @return string
     */
    public function getPrefix($preferDash = false)
    {
        return $this->container['starter.prefix'] . ($preferDash ? '-' : '_');
    }

    /**
     * Return prefixed string.
     *
     * @param string $string     InputWidget string.
     * @param bool   $preferDash Use dash or underscore.
     *
     * @return string
     */
    public function prefixed($string, $preferDash = false)
    {
        return $this->getPrefix($preferDash) . $string;
    }

    /**
     * Return allowed blog id list under multisite condition.
     *
     * @return int[]|callable
     */
    public function getBlogId()
    {
        return $this->container['starter.blog_id'];
    }

    /**
     * Return plugin's default action, filter priority value.
     *
     * @return int
     */
    public function getDefaultPriority()
    {
        return $this->container['starter.default_priority'];
    }

    /**
     * Return Blade template use.
     *
     * @return bool
     */
    public function useBlade()
    {
        return $this->container['starter.use_blade'];
    }

    /**
     * Return Eloquent ORM use.
     *
     * @return bool
     */
    public function useEloquent()
    {
        return $this->container['starter.use_eloquent'];
    }

    protected function isAvailable()
    {
        if (is_multisite()) {
            $blogId        = $this->getBlogId();
            $currentBlogId = get_current_blog_id();

            return (
                null === $blogId ||
                (is_array($blogId) && in_array($currentBlogId, $blogId)) ||
                (is_callable($blogId) && call_user_func($blogId, $this))
            );
        } else {
            return true;
        }
    }

    /**
     * @throws StarterFailureException
     */
    protected function prepareBlade()
    {
        if ($this->useBlade()) {
            $container = $this->getContainer();

            $container->bindIf('config', Repository::class, true);
            $container->bindIf('files', Filesystem::class, true);
            $container->bindIf('events', Dispatcher::class, true);

            $config = $container->get('config');
            $config->set(
                [
                    'view.compiled' => $this->getBladeCachePath(),
                    'view.paths'    => [
                        get_stylesheet_directory() . "/{$this->getSlug()}/templates",
                        get_template_directory() . "/{$this->getSlug()}/templates",
                        dirname($this->getMainFile()) . '/src/templates',
                        dirname(AXIS_MAIN) . '/src/templates',
                    ],
                ]
            );

            /** @noinspection PhpParamsInspection */
            (new ViewServiceProvider($container))->register();
        }
    }

    protected function prepareEloquent()
    {
        if ($this->useEloquent()) {
            $this->getContainer()->bindIf(
                'db',
                function () {
                    global $wpdb;

                    /** @noinspection PhpParamsInspection */
                    return new Connection($wpdb, DB_NAME, $wpdb->prefix);
                },
                true // shared.
            );
        }
    }

    /**
     * @throws StarterFailureException
     */
    protected function getBladeCachePath()
    {
        $dirs      = wp_get_upload_dir();
        $cachePath = "{$dirs['basedir']}/naran-axis/{$this->getSlug()}/blade.cache";

        if (is_file($cachePath)) {
            throw new StarterFailureException(
                sprintf(__('Cache path \'%s\' is already exists, but it is a file.', 'naran-axis'), $cachePath)
            );
        } elseif ( ! file_exists($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        if (is_dir($cachePath) && ! (is_writable($cachePath) && is_executable($cachePath))) {
            throw new StarterFailureException(
                sprintf(
                    __('Cache path \'%s\' must be an accessible and writable directory.', 'naran-axis'),
                    $cachePath
                )
            );
        }

        return $cachePath;
    }
}
