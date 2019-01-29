<?php

namespace FondOfSpryker\Zed\CategoryDataImport;

use FondOfSpryker\Zed\CategoryDataImport\Dependency\Service\CategoryDataImportToUtilTextBridge;
use Spryker\Zed\CategoryDataImport\CategoryDataImportDependencyProvider as BaseCategoryDataImportDependencyProvider;
use Spryker\Zed\Kernel\Container;

class CategoryDataImportDependencyProvider extends BaseCategoryDataImportDependencyProvider
{
    public const SERVICE_UTIL_TEXT = 'SERVICE_UTIL_TEXT';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container)
    {
        $container = parent::provideBusinessLayerDependencies($container);

        $container[self::SERVICE_UTIL_TEXT] = function (Container $container) {
            return new CategoryDataImportToUtilTextBridge($container->getLocator()->utilText()->service());
        };

        return $container;
    }
}
