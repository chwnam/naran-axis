<?php


namespace Naran\Axis\Starter\ClassFinder;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileInfo;


/**
 * Class AutoDiscoverClassFinder
 *
 * @package Naran\Axis\Starter\ClassFinder
 */
class AutoDiscoverClassFinder implements ClassFinder
{
    private $component;

    private $targets = [];

    private $found = null;

    public function __construct($component, $rootNamespace, $rootSrcPath)
    {
        $this->component = (array)$component;
        $this->addRootPair($rootNamespace, $rootSrcPath);
    }

    public function find()
    {
        if (is_null($this->found)) {
            $c = '(' . implode('|', $this->component) . ')';

            foreach ($this->targets as $prefix => $root) {
                $iterator = new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)),
                    // e.g.
                    // #1: {$region}/{$component}/{$context}/..../file.php
                    // #2: {$component}/{$context}/.../file.php
                    ";([^/]+/)?{$c}/.+\.php$;",
                    RecursiveRegexIterator::MATCH
                );

                /** @var SplFileInfo $item */
                foreach ($iterator as $item) {
                    [$region, $component, $context, $fqcn] = $this->extractContextFqcn($item, $prefix, $root);

                    $this->found[] = [
                        $region,
                        $component,
                        $context,
                        $item->getRealPath(),
                        $fqcn,
                    ];
                }
            }
        }

        return $this->found;
    }

    public function addRootPair($namespace, $srcPath)
    {
        $namespace = trim($namespace, '\\') . '\\';
        $srcPath   = rtrim($srcPath, DIRECTORY_SEPARATOR);

        $this->targets[$namespace] = $srcPath;

        return $this;
    }

    public function getComponent()
    {
        return $this->component;
    }

    protected function extractContextFqcn(SplFileInfo $info, $prefix, $path)
    {
        $realPath  = $info->getRealPath();
        $basename  = $info->getBasename();
        $className = $info->getBasename('.php');

        $pathLen = strlen($path);
        $tail    = substr($realPath, $pathLen + 1, strlen($realPath) - ($pathLen + strlen($basename) + 1));
        $tailNs  = str_replace(DIRECTORY_SEPARATOR, '\\', $tail); // namespace with trailing backslash.
        $fqcn    = "{$prefix}{$tailNs}{$className}";

        // With region    - 0th: Region, 1st: Component, 2nd: Context.
        // Without region - 0th: Component, 1st: Context.
        $parts = explode('\\', $tailNs);

        if (count($parts) > 2 && in_array($parts[1], $this->component)) {
            $region    = $parts[0];
            $component = $parts[1];
            $context   = $parts[2];
        } elseif (count($parts) > 1 && in_array($parts[0], $this->component)) {
            $region    = '';
            $component = $parts[0];
            $context   = $parts[1];
        } else {
            $region    = '';
            $component = '';
            $context   = '';
        }

        return [$region, $component, $context, $fqcn];
    }
}
