<?php

namespace FondOfSpryker\Zed\CategoryDataImport;

use Spryker\Zed\CategoryDataImport\CategoryDataImportConfig as SprykerCategoryDataImportConfig;

class CategoryDataImportConfig extends SprykerCategoryDataImportConfig
{
    /**
     * @return \Generated\Shared\Transfer\DataImporterConfigurationTransfer
     */
    public function getCategoryDataImporterConfiguration()
    {
        return $this->buildImporterConfiguration(
            $this->getDataImportRootPath() . 'category.csv',
            static::IMPORT_TYPE_CATEGORY
        );
    }
}
