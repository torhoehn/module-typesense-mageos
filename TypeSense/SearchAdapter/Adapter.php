<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

use Exception;
use Magento\AdvancedSearch\Model\Client\ClientInterface;
use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder as AggregationBuilder;
use Magento\Elasticsearch\SearchAdapter\QueryContainerFactory;
use Magento\Elasticsearch\SearchAdapter\ResponseFactory;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use MageOs\TypeSense\Mapper\QueryMapper;
use MageOs\TypeSense\Model\Config;
use MageOs\TypeSense\SearchAdapter\BatchDataMapper\DataMapperResolver;
use Psr\Log\LoggerInterface;

class Adapter implements AdapterInterface
{
    public const BULK_ACTION_INDEX = 'index';
    public const BULK_ACTION_CREATE = 'create';
    public const BULK_ACTION_DELETE = 'delete';
    public const BULK_ACTION_UPDATE = 'update';

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

    /**
     * @var ClientInterface $client
     */
    private $client;

    public function __construct(
        private ConnectionManager $connectionManager,
        private QueryMapper $mapper,
        private Config $clientConfig,
        private ResponseFactory $responseFactory,
        private AggregationBuilder $aggregationBuilder,
        private QueryContainerFactory $queryContainerFactory,
        private DataMapperResolver $batchDocumentDataMapper,
        private LoggerInterface $logger,
        $options = [],
    ) {
        try {
            $this->client = $this->connectionManager->getConnection($options);
        } catch (Exception $e) {
            $this->logger->critical($e);
            throw new LocalizedException(
                __('The search failed because of a search engine misconfiguration.')
            );
        }
    }

    /**
     * Search query
     */
    public function query(RequestInterface $request) : QueryResponse
    {
        $client = $this->connectionManager->getConnection();
        $aggregationBuilder = $this->aggregationBuilder;
        $query = $this->mapper->buildQuery($request);

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

    /**
     * Create Typesense documents by specified data
     *
     * @throws ConfigurationMismatchException
     * @throws NoSuchEntityException
     */
    public function prepareDocsPerStore(array $documentData, int $storeId): array
    {
        $documents = [];
        if (count($documentData)) {
            $documents = $this->batchDocumentDataMapper->map(
                $documentData,
                $storeId
            );
        }
        return $documents;
    }

    public function addDocs(array $documents, int $storeId, string $mappedIndexerId): void
    {
        $a = 10;
    }

    public function updateAlias(int $storeId, string $mappedIndexerId): void
    {
        $a = 10;
    }

    /**
     * Delete documents from Typesense index by Ids
     */
    public function deleteDocs(array $documentIds, int $storeId, string $mappedIndexerId): void
    {
        $a = 10;
    }

    /**
     * Removes all documents from Typesense index
     */
    public function cleanIndex(int $storeId, string $mappedIndexerId): void
    {
        $a = 10;
    }

    /**
     * @throws LocalizedException
     */
    public function ping(): bool
    {
        return $this->client->ping();
    }

    /**
     * Update Typesense mapping for index.
     */
    public function updateIndexMapping(int $storeId, string $mappedIndexerId, string $attributeCode): void
    {
        $a = 10;
    }

    /**
     * Checks whether TypeSense index and alias exists.
     */
    public function checkIndex(int $storeId, string $mappedIndexerId, bool $checkAlias = true): void
    {
        $a = 10;
    }
}
