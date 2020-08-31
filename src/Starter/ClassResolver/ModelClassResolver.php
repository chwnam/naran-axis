<?php


namespace Naran\Axis\Starter\ClassResolver;


use Naran\Axis\Model\ActivationDeactivation;
use Naran\Axis\Model\Holder\MetaHolder;
use Naran\Axis\Model\Holder\OptionHolder;
use Naran\Axis\Model\Model;
use Naran\Axis\Model\PostModel;
use Naran\Axis\Model\TaxonomyModel;
use Naran\Axis\Starter\ClassFinder\ClassFinder;
use Naran\Axis\Starter\Starter;

class ModelClassResolver implements ClassResolver
{
    private $starter;

    private $finder;

    private $regionFilter;

    public function __construct(
        Starter $starter,
        ClassFinder $finder,
        RegionFilter $regionFilter
    ) {
        $this->starter      = $starter;
        $this->finder       = $finder;
        $this->regionFilter = $regionFilter;
    }

    public function resolve()
    {
        $container = $this->starter->getContainer();
        $priority  = $this->starter->getDefaultPriority();
        $file      = plugin_basename($this->starter->getMainFile());

        foreach ($this->finder->find() as [$region, $component, $context, $path, $fqcn]) {
            if (
                $this->regionFilter->filter($region, $this->starter) &&
                ($parents = class_parents($fqcn)) &&
                isset($parents[Model::class])
            ) {
                if (isset($parents[MetaHolder::class])) {
                    /** @var MetaHolder $model */
                    $model = $container->get($fqcn);
                    $model->registerFields();

                    if (isset($parents[PostModel::class])) {
                        /** @var PostModel $model */
                        $model->registerPostType();
                    } elseif ($parents[TaxonomyModel::class]) {
                        /** @var TaxonomyModel $model */
                        $model->registerTaxonomy();
                    }
                } elseif (isset($parents[OptionHolder::class])) {
                    /** @var OptionHolder $model */
                    $model = $container->get($fqcn);
                    $model->registerFields();
                }

                $implements = class_implements($fqcn);

                if ($implements && isset($implements[ActivationDeactivation::class])) {
                    $model = $container->get($fqcn);
                    add_action("activate_{$file}", [$model, 'activationSetup'], $priority);
                    add_action("deactivate_{$file}", [$model, 'deactivationCleanup'], $priority);
                }
            }
        }
    }
}
