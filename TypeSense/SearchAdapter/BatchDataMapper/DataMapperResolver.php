<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\BatchDataMapper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ConfigurationMismatchException;
use MageOs\TypeSense\SearchAdapter\BatchDataMapperInterface;
use MageOs\TypeSense\Model\Config;

/**
 * Map index data to search engine metadata
 */
class DataMapperResolver implements BatchDataMapperInterface
{
    /** @var array<string, BatchDataMapperInterface> */
    private array $dataMapperEntity = [];

    public function __construct(private readonly DataMapperFactory $dataMapperFactory)
    {
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws NoSuchEntityException
     */
    public function map(array $documentData, int $storeId, array $context = []): array
    {
        $entityType = $context['entityType'] ?? Config::TYPESENSE_TYPE_DEFAULT;
        return $this->getDataMapper($entityType)->map($documentData, $storeId, $context);
    }

    /**
     * Get instance of data mapper for specified entity type
     *
     * @throws NoSuchEntityException
     * @throws ConfigurationMismatchException
     */
    private function getDataMapper(string $entityType): BatchDataMapperInterface
    {
        if (!isset($this->dataMapperEntity[$entityType])) {
            $this->dataMapperEntity[$entityType] = $this->dataMapperFactory->create($entityType);
        }

        return $this->dataMapperEntity[$entityType];
    }
}
