<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\Aggregation;

use Magento\Framework\Search\Dynamic\IntervalInterface;
use MageOs\TypeSense\SearchAdapter\ConnectionManager;
use MageOs\TypeSense\SearchAdapter\SearchIndexNameResolver;

/**
 * class reference \Magento\Elasticsearch\Elasticsearch5\SearchAdapter\Aggregation\Interval
 */
class Interval implements IntervalInterface
{
    public function __construct(
        private readonly SearchIndexNameResolver $searchIndexNameResolver,
        private readonly ConnectionManager $connectionManager,
        string $fieldName,
        string $storeId,
        array $entityIds
    ) {
    }

    public function load($limit, $offset = null, $lower = null, $upper = null): array
    {
        return [];
    }

    public function loadPrevious($data, $index, $lower = null): array
    {
        return [];
    }

    public function loadNext($data, $rightIndex, $upper = null): array
    {
        return [];
    }
}
