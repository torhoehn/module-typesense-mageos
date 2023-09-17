<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

/**
 * Map index data to search engine metadata
 * Convert array [[attribute_id => [entity_id => value], ... ]] to applicable for search engine [[attribute => value],]
 */
interface BatchDataMapperInterface
{
    public function map(array $documentData, int $storeId, array $context = []): array;
}
