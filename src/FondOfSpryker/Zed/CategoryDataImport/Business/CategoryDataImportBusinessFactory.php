<?php

namespace FondOfSpryker\Zed\CategoryDataImport\Business;

use FondOfSpryker\Zed\CategoryDataImport\Business\Model\CategoryWriterStep;
use FondOfSpryker\Zed\CategoryDataImport\CategoryDataImportDependencyProvider;
use FondOfSpryker\Zed\CategoryDataImport\Dependency\Service\CategoryDataImportToUtilTextInterface;
use Spryker\Shared\Kernel\Store;
use Spryker\Zed\CategoryDataImport\Business\CategoryDataImportBusinessFactory as SprykerCategoryDataImportBusinessFactory;

class CategoryDataImportBusinessFactory extends SprykerCategoryDataImportBusinessFactory
{
    /**
     * @return \Spryker\Zed\DataImport\Business\Model\DataImporterInterface
     */
    public function createCategoryImporter()
    {
        $dataImporter = $this->getCsvDataImporterFromConfig($this->getConfig()->getCategoryDataImporterConfiguration());

        $dataSetStepBroker = $this->createTransactionAwareDataSetStepBroker();
        $dataSetStepBroker
            ->addStep($this->createAddLocalesStep())
            ->addStep($this->createLocalizedAttributesExtractorStep([
                CategoryWriterStep::KEY_NAME,
                CategoryWriterStep::KEY_META_TITLE,
                CategoryWriterStep::KEY_META_DESCRIPTION,
                CategoryWriterStep::KEY_META_KEYWORDS,
            ]))
            ->addStep($this->createCategoryWriterStep());

        $dataImporter
            ->addDataSetStepBroker($dataSetStepBroker);

        return $dataImporter;
    }

    /**
     * @return \Pyz\Zed\CategoryDataImport\Business\Model\CategoryWriterStep
     */
    public function createCategoryWriterStep(): CategoryWriterStep
    {
        return new CategoryWriterStep(
            $this->createCategoryRepository(),
            $this->getStore(),
            $this->getUtilText()
        );
    }

    /**
     * @return \Pyz\Zed\CategoryDataImport\Dependency\Service\CategoryDataImportToUtilTextInterface
     */
    protected function getUtilText(): CategoryDataImportToUtilTextInterface
    {
        return $this->getProvidedDependency(CategoryDataImportDependencyProvider::SERVICE_UTIL_TEXT);
    }

    /**
     * @return \Spryker\Shared\Kernel\Store
     */
    protected function getStore(): Store
    {
        return $this->getProvidedDependency(CategoryDataImportDependencyProvider::STORE);
    }

}