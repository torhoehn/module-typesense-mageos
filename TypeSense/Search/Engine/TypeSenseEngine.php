<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Search\Engine;

use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogSearch\Model\ResourceModel\EngineInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class TypeSenseEngine implements EngineInterface
{
    public function __construct(
        private readonly Visibility $catalogProductVisibility,
        private readonly IndexScopeResolver $indexScopeResolver
    ) {
    }

    /**
     * Retrieve allowed visibility values for current engine
     *
     * @return int[]
     */
    public function getAllowedVisibility(): array
    {
        return $this->catalogProductVisibility->getVisibleInSiteIds();
    }

    /**
     * Define if current search engine supports advanced index
     *
     * @return bool
     */
    public function allowAdvancedIndex(): bool
    {
        return false;
    }

    public function processAttributeValue($attribute, $value)
    {
        return $value;
    }

    /**
     * Prepare index array as a string glued by separator
     *
     * Support 2 level array gluing
     *
     * @param array $index
     * @param string $separator
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareEntityIndex($index, $separator = ' '): array
    {
        return $index;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
