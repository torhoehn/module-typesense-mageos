<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Indexer;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Search\Request\Dimension;
use MageOs\TypeSense\SearchAdapter\Adapter as TypeSenseAdapter;
use MageOs\TypeSense\SearchAdapter\SearchIndexNameResolver;

class IndexerHandler implements IndexerInterface
{
    public const DEFAULT_BATCH_SIZE = 500;
    private const DEPLOYMENT_CONFIG_INDEXER_BATCHES = 'indexer/batch_size/';

    public function __construct(
        private readonly IndexStructureInterface $indexStructure,
        private readonly TypeSenseAdapter $adapter,
        private readonly SearchIndexNameResolver $indexNameResolver,
        private readonly Batch $batch,
        private readonly ScopeResolverInterface  $scopeResolver,
        private readonly array $data = [],
        private int $batchSize = self::DEFAULT_BATCH_SIZE,
        private ?DeploymentConfig $deploymentConfig = null,
        private ?CacheContext $cacheContext = null,
        private ?Processor $processor = null
    ) {
        $this->deploymentConfig = $deploymentConfig ?: ObjectManager::getInstance()->get(DeploymentConfig::class);
        $this->cacheContext = $cacheContext ?: ObjectManager::getInstance()->get(CacheContext::class);
        $this->processor = $processor ?: ObjectManager::getInstance()->get(Processor::class);
    }

    /**
     * @throws Exception
     */
    public function saveIndex($dimensions, \Traversable $documents): IndexerInterface
    {
        $dimension = array_shift($dimensions);
        $scopeId = (int)$this->scopeResolver->getScope($dimension->getValue())->getId();

        $this->batchSize = $this->deploymentConfig->get(
            self::DEPLOYMENT_CONFIG_INDEXER_BATCHES . Fulltext::INDEXER_ID . '/elastic_save'
        ) ?? $this->batchSize;

        foreach ($this->batch->getItems($documents, $this->batchSize) as $documentsBatch) {
            $docs = $this->adapter->prepareDocsPerStore($documentsBatch, $scopeId);
            $this->adapter->addDocs($docs, $scopeId, $this->getIndexerId());
            if ($this->processor->getIndexer()->isScheduled()) {
                $this->updateCacheContext($docs);
            }
        }
        $this->adapter->updateAlias($scopeId, $this->getIndexerId());
        return $this;
    }

    /**
     * Add category cache tags for the affected products to the cache context
     */
    private function updateCacheContext(array $docs): void
    {
        $categoryIds = [];

        foreach ($docs as $document) {
            if (!empty($document['category_ids'])) {
                if (is_array($document['category_ids'])) {
                    foreach ($document['category_ids'] as $id) {
                        $categoryIds[] = $id;
                    }
                } elseif (is_numeric($document['category_ids'])) {
                    $categoryIds[] = $document['category_ids'];
                }
            }
        }

        if (!empty($categoryIds)) {
            $categoryIds = array_unique($categoryIds);
            $this->cacheContext->registerEntities(Category::CACHE_TAG, $categoryIds);
        }
    }

    /**
     * @throws Exception
     */
    public function deleteIndex($dimensions, \Traversable $documents): IndexerInterface
    {
        $dimension = array_shift($dimensions);

        if (!$dimension instanceof Dimension) {
            throw new \InvalidArgumentException('Dimensions must be an instance of Dimension objects');
        }

        $scopeId = $this->scopeResolver->getScope($dimension->getValue())->getId();
        $documentIds = [];
        foreach ($documents as $document) {
            $documentIds[$document] = $document;
        }

        $this->adapter->deleteDocs($documentIds, $scopeId, $this->getIndexerId());

        return $this;
    }

    public function cleanIndex($dimensions): IndexerInterface
    {
        $this->indexStructure->delete($this->getIndexerId(), $dimensions);
        $this->indexStructure->create($this->getIndexerId(), [], $dimensions);
        return $this;
    }

    public function isAvailable($dimensions = []): bool
    {
        return $this->adapter->ping();
    }

    /**
     * @throws Exception
     */
    public function updateIndex(array $dimensions, string $attributeCode): void
    {
        $dimension = current($dimensions);
        $scopeId = (int)$this->scopeResolver->getScope($dimension->getValue())->getId();
        $this->adapter->updateIndexMapping($scopeId, $this->getIndexerId(), $attributeCode);
    }

    private function getIndexerId(): string
    {
        return $this->indexNameResolver->getIndexMapping($this->data['indexer_id']);
    }
}
