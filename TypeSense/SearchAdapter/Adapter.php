<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder as AggregationBuilder;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\SearchAdapter\QueryContainerFactory;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use MageOs\TypeSense\Mapper\QueryMapper;
use Psr\Log\LoggerInterface;

/**
 * TypeSense Search Adapter
 */
class Adapter implements AdapterInterface
{
    /**
     * Empty response from TypeSense
     */
    private static array $emptyRawResponse = [
        'hits' => [
            'hits' => []
        ],
        'aggregations' => [
            'price_bucket' => [],
            'category_bucket' => [
                'buckets' => []
            ]
        ]
    ];

    public function __construct(
        private ConnectionManager $connectionManager,
        private QueryMapper $mapper,
        private ResponseFactory $responseFactory,
        private AggregationBuilder $aggregationBuilder,
        private QueryContainerFactory $queryContainerFactory,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Search query
     */
    public function query(RequestInterface $request) : QueryResponse
    {
        $client = $this->connectionManager->getConnection();
        $aggregationBuilder = $this->aggregationBuilder;
        $query = $this->mapper->buildQuery($request);
        $aggregationBuilder->setQuery($this->queryContainerFactory->create(['query' => $query]));

        try {
            $rawResponse = $client->query($query);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            // return empty search result in case an exception is thrown from OpenSearch
            $rawResponse = self::$emptyRawResponse;
        }

        $rawDocuments = $rawResponse['hits']['hits'] ?? [];

        return $this->responseFactory->create(
            [
                'documents' => $rawDocuments,
                'aggregations' => $aggregationBuilder->build($request, $rawResponse),
                'total' => $rawResponse['hits']['total']['value'] ?? 0
            ]
        );
    }
}
