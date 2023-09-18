<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Elasticsearch\Model\Config;

class SearchIndexNameResolver
{
    public function __construct(
        private readonly Config $clientConfig
    ) {
    }

    public function getIndexName(int $storeId, string $indexerId): string
    {
        $mappedIndexerId = $this->getIndexMapping($indexerId);
        return $this->clientConfig->getIndexPrefix() . '_' . $mappedIndexerId . '_' . $storeId;
    }

    public function getIndexMapping(string $indexerId): string
    {
        $mappedIndexerId = $indexerId;

        if ($indexerId === Fulltext::INDEXER_ID) {
            $mappedIndexerId = 'product';
        }

        return $mappedIndexerId;
    }
}
