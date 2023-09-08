<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Mapper;

use Magento\Elasticsearch\Elasticsearch5\SearchAdapter\Mapper;
use Magento\Framework\Search\RequestInterface;

/**
 * TypeSense mapper class for a query building
 */
class QueryMapper
{
    public function __construct(private Mapper $mapper)
    {
    }

    /**
     * Build adapter dependent query
     */
    public function buildQuery(RequestInterface $request) : array
    {
        $searchQuery = $this->mapper->buildQuery($request);
        $searchQuery['track_total_hits'] = true;
        return $searchQuery;
    }
}
