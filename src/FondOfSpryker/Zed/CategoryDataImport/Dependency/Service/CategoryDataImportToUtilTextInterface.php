<?php

namespace FondOfSpryker\Zed\CategoryDataImport\Dependency\Service;

interface CategoryDataImportToUtilTextInterface
{
    /**
     * @param string $value
     *
     * @return string
     */
    public function generateSlug(string $value): string;
}
