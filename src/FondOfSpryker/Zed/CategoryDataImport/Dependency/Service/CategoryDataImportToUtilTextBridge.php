<?php

namespace FondOfSpryker\Zed\CategoryDataImport\Dependency\Service;

class CategoryDataImportToUtilTextBridge implements CategoryDataImportToUtilTextInterface
{
    /**
     * @var \Spryker\Service\UtilText\UtilTextServiceInterface
     */
    protected $utilTextService;

    /**
     * @param \Spryker\Service\UtilText\UtilTextServiceInterface $utilTextService
     */
    public function __construct($utilTextService)
    {
        $this->utilTextService = $utilTextService;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function generateSlug(string $value): string
    {
        return $this->utilTextService->generateSlug($value);
    }
}
