<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\Dynamic;

use Magento\Catalog\Model\Layer\Filter\Price\Range;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Framework\Search\Dynamic\EntityStorage;
use Magento\Framework\Search\Dynamic\IntervalFactory;
use Magento\Framework\Search\Dynamic\IntervalInterface;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOs\TypeSense\Model\Adapter\FieldMapperInterface;
use MageOs\TypeSense\Model\Config;
use MageOs\TypeSense\SearchAdapter\ConnectionManager;
use MageOs\TypeSense\SearchAdapter\QueryContainer;
use MageOs\TypeSense\SearchAdapter\SearchIndexNameResolver;
use Psr\Log\LoggerInterface;

class DataProvider implements DataProviderInterface
{
    /**
     * Default field name used to aggregate data
     */
    private const DEFAULT_AGGREGATION_FIELD = 'price';
    private string $aggregationFieldName;

    public function __construct(
        private ConnectionManager $connectionManager,
        private FieldMapperInterface $fieldMapper,
        private Range $range,
        private IntervalFactory $intervalFactory,
        private Config $clientConfig,
        private StoreManagerInterface $storeManager,
        private SearchIndexNameResolver $searchIndexNameResolver,
        private string $indexerId,
        private ScopeResolverInterface $scopeResolver,
        private readonly QueryContainer $queryContainer,
        private ?LoggerInterface $logger = null,
        ?string $aggregationFieldName = null
    ) {
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->aggregationFieldName = $aggregationFieldName ?? self::DEFAULT_AGGREGATION_FIELD;
    }

    public function getRange(): array
    {
        return $this->range->getPriceRange();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getAggregations(EntityStorage $entityStorage): array
    {
        $aggregations = [
            'count' => 0,
            'max' => 0,
            'min' => 0,
            'std' => 0,
        ];

        $query = $this->getBasicSearchQuery($entityStorage);

        $fieldName = $this->fieldMapper->getFieldName($this->aggregationFieldName);
        $query['body']['aggregations'] = [
            'prices' => [
                'extended_stats' => [
                    'field' => $fieldName,
                ],
            ],
        ];

        try {
            $queryResult = $this->connectionManager->getConnection()->query($query);
            if (isset($queryResult['aggregations']['prices'])) {
                $aggregations = [
                    'count' => $queryResult['aggregations']['prices']['count'],
                    'max' => $queryResult['aggregations']['prices']['max'],
                    'min' => $queryResult['aggregations']['prices']['min'],
                    'std' => $queryResult['aggregations']['prices']['std_deviation'],
                ];
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $aggregations;
    }

    public function getInterval(
        BucketInterface $bucket,
        array $dimensions,
        EntityStorage $entityStorage
    ): IntervalInterface {
        $entityIds = $entityStorage->getSource();
        $fieldName = $this->fieldMapper->getFieldName($this->aggregationFieldName);
        $dimension = array_shift($dimensions);
        $storeId = $this->scopeResolver->getScope($dimension->getValue())->getId();

        return $this->intervalFactory->create(
            [
                'entityIds' => $entityIds,
                'storeId' => $storeId,
                'fieldName' => $fieldName,
            ]
        );
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getAggregation(
        BucketInterface $bucket,
        array $dimensions,
        $range,
        EntityStorage $entityStorage
    ): array {
        $query = $this->getBasicSearchQuery($entityStorage);

        $fieldName = $this->fieldMapper->getFieldName($bucket->getField());
        $query['body']['aggregations'] = [
            'prices' => [
                'histogram' => [
                    'field' => $fieldName,
                    'interval' => (float)$range,
                    'min_doc_count' => 1,
                ],
            ],
        ];

        $result = [];
        try {
            $queryResult = $this->connectionManager->getConnection()->query($query);
            foreach ($queryResult['aggregations']['prices']['buckets'] as $bucketItem) {
                $key = (int)($bucketItem['key'] / $range + 1);
                $result[$key] = $bucketItem['doc_count'];
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }

    public function prepareData($range, array $dbRanges): array
    {
        $data = [];

        foreach ($dbRanges as $index => $count) {
            $fromPrice = $index === 1 ? 0 : ($index - 1) * $range;
            $toPrice = $index * $range;
            $data[] = [
                'from' => $fromPrice,
                'to' => $toPrice,
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getBasicSearchQuery(
        EntityStorage $entityStorage,
        array $dimensions = []
    ): array {
        if (null !== $this->queryContainer) {
            return $this->queryContainer->getQuery();
        }

        $entityIds = $entityStorage->getSource();

        $dimension = array_shift($dimensions);
        $storeId = false !== $dimension
            ? $this->scopeResolver->getScope($dimension->getValue())->getId()
            : $this->storeManager->getStore()->getId();

        $query = [
            'index' => $this->searchIndexNameResolver->getIndexName($storeId, $this->indexerId),
            'type' => $this->clientConfig->getEntityType(),
            'body' => [
                'fields' => [
                    '_id',
                    '_score',
                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'terms' => [
                                    '_id' => $entityIds,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $query;
    }
}
