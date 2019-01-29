<?php

namespace FondOfSpryker\Zed\CategoryDataImport\Business\Model;

use FondOfSpryker\Zed\CategoryDataImport\Dependency\Service\CategoryDataImportToUtilTextInterface;
use Exception;
use Orm\Zed\Category\Persistence\SpyCategory;
use Orm\Zed\Category\Persistence\SpyCategoryAttribute;
use Orm\Zed\Category\Persistence\SpyCategoryNode;
use Orm\Zed\Category\Persistence\SpyCategoryNodeQuery;
use Orm\Zed\Url\Persistence\SpyUrlQuery;
use Spryker\Shared\Kernel\Store;
use Spryker\Zed\Category\Dependency\CategoryEvents;
use Spryker\Zed\CategoryDataImport\Business\Model\CategoryWriterStep as SprykerCategoryWriterStep;
use Spryker\Zed\CategoryDataImport\Business\Model\Reader\CategoryReaderInterface;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\AddLocalesStep;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\Url\Dependency\UrlEvents;

class CategoryWriterStep extends SprykerCategoryWriterStep
{

    /**
     * @var \Spryker\Shared\Kernel\Store
     */
    protected $store;

    /**
     * @var \Pyz\Zed\CategoryDataImport\Dependency\Service\CategoryDataImportToUtilTextInterface
     */
    protected $categoryDataImportToUtilText;

    /**
     * @param \Spryker\Zed\CategoryDataImport\Business\Model\Reader\CategoryReaderInterface $categoryReader
     * @param \Spryker\Shared\Kernel\Store $store
     * @param \FondOfSpryker\Zed\CategoryDataImport\Dependency\Service\CategoryDataImportToUtilTextInterface $categoryDataImportToUtilText
     */
    public function __construct(
        CategoryReaderInterface $categoryReader,
        Store $store,
        CategoryDataImportToUtilTextInterface $categoryDataImportToUtilText
    ) {
        parent::__construct($categoryReader);

        $this->store = $store;
        $this->categoryDataImportToUtilText = $categoryDataImportToUtilText;
    }

    /**
     * @param \Orm\Zed\Category\Persistence\SpyCategory $categoryEntity
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return \Orm\Zed\Category\Persistence\SpyCategoryNode
     */
    protected function findOrCreateNode(SpyCategory $categoryEntity, DataSetInterface $dataSet)
    {
        $categoryNodeEntity = SpyCategoryNodeQuery::create()
            ->filterByCategory($categoryEntity)
            ->findOneOrCreate();

        if (!empty($dataSet[static::KEY_PARENT_CATEGORY_KEY])) {
            $idParentCategoryNode = $this->categoryReader->getIdCategoryNodeByCategoryKey($dataSet[static::KEY_PARENT_CATEGORY_KEY]);
            $categoryNodeEntity->setFkParentCategoryNode($idParentCategoryNode);
        }

        $categoryNodeEntity->fromArray($dataSet->getArrayCopy());

        if ($categoryNodeEntity->isNew() || $categoryNodeEntity->isModified()) {
            $categoryNodeEntity->save();
        }

        $this->addToClosureTable($categoryNodeEntity);
        $this->addPublishEvents(CategoryEvents::CATEGORY_NODE_PUBLISH, $categoryNodeEntity->getIdCategoryNode());

        foreach ($categoryEntity->getAttributes() as $categoryAttributesEntity) {
            $urlPathParts = $this->getUrlPathParts($dataSet, $categoryNodeEntity, $categoryAttributesEntity);

            if ($categoryNodeEntity->getIsRoot()) {
                $this->addPublishEvents(
                    CategoryEvents::CATEGORY_TREE_PUBLISH,
                    $categoryNodeEntity->getIdCategoryNode()
                );
            }

            $url = '/' . implode('/', $this->convertUrlPathParts($urlPathParts));

            $urlEntity = SpyUrlQuery::create()
                ->filterByFkLocale($categoryAttributesEntity->getFkLocale())
                ->filterByFkResourceCategorynode($categoryNodeEntity->getIdCategoryNode())
                ->findOneOrCreate();

            $urlEntity
                ->setUrl($url);

            if ($urlEntity->isNew() || $urlEntity->isModified()) {
                $urlEntity->save();
                $this->addPublishEvents(UrlEvents::URL_PUBLISH, $urlEntity->getIdUrl());
            }
        }

        return $categoryNodeEntity;
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     * @param \Orm\Zed\Category\Persistence\SpyCategoryNode $categoryNodeEntity
     * @param \Orm\Zed\Category\Persistence\SpyCategoryAttribute $categoryAttributesEntity
     *
     * @return array
     */
    protected function getUrlPathParts(
        DataSetInterface $dataSet,
        SpyCategoryNode $categoryNodeEntity,
        SpyCategoryAttribute $categoryAttributesEntity
    ): array {
        $idLocale = $categoryAttributesEntity->getFkLocale();
        $languageIdentifier = $this->getLanguageIdentifier($idLocale, $dataSet);

        $urlPathParts = [$languageIdentifier];
        if (!$categoryNodeEntity->getIsRoot()) {
            $parentUrl = $this->categoryReader->getParentUrl(
                $dataSet[static::KEY_PARENT_CATEGORY_KEY],
                $idLocale
            );

            $urlPathParts = explode('/', ltrim($parentUrl, '/'));
            $urlPathParts[] = $categoryAttributesEntity->getName();
        }

        return $urlPathParts;
    }

    /**
     * @param int $idLocale
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getLanguageIdentifier($idLocale, DataSetInterface $dataSet): string
    {
        $allowedLocales = $this->getAllowedLocales();

        foreach ($dataSet[AddLocalesStep::KEY_LOCALES] as $localeName => $localeId) {
            if ($idLocale !== $localeId) {
                continue;
            }

            $key = \array_search($localeName, $allowedLocales, true);

            if ($key !== false) {
                return $key;
            }
        }

        throw new Exception(sprintf('Could not extract language identifier for idLocale "%s"', $idLocale));
    }

    /**
     * @param array $urlPathParts
     *
     * @return array
     */
    protected function convertUrlPathParts(array $urlPathParts): array
    {
        $slugGenerator = $this->categoryDataImportToUtilText;

        $convertCallback = function ($value) use ($slugGenerator) {
            return $slugGenerator->generateSlug($value);
        };

        $urlPathParts = array_map($convertCallback, $urlPathParts);

        return $urlPathParts;
    }

    /**
     * @return array
     */
    protected function getAllowedLocales(): array
    {
        $store = Store::getInstance();
        $storeNames = $store->getAllowedStores();
        $allowedLocales = [];

        foreach ($storeNames as $storeName) {
            $locales = $store->getLocalesPerStore($storeName);
            $allowedLocales = \array_merge($allowedLocales, $locales);
        }

        return $allowedLocales;
    }

}
