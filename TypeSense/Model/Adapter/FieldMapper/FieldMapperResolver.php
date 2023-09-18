<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Model\Adapter\FieldMapper;

use Exception;
use Magento\Elasticsearch\Model\Config;
use Magento\Framework\ObjectManagerInterface;
use MageOs\TypeSense\Model\Adapter\FieldMapperInterface;

class FieldMapperResolver implements FieldMapperInterface
{
    /** @var FieldMapperInterface[] */
    private array $fieldMapperEntity = [];

    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly array $fieldMappers = []
    ) {
    }

    /**
     * @throws Exception
     */
    public function getFieldName(string $attributeCode, array $context = []): string
    {
        $entityType = $context['entityType'] ?? Config::ELASTICSEARCH_TYPE_DEFAULT;
        return $this->getEntity($entityType)->getFieldName($attributeCode, $context);
    }

    /**
     * @throws Exception
     */
    public function getAllAttributesTypes(array $context = []): array
    {
        $entityType = $context['entityType'] ?? Config::ELASTICSEARCH_TYPE_DEFAULT;
        return $this->getEntity($entityType)->getAllAttributesTypes($context);
    }

    /**
     * @throws Exception
     */
    private function getEntity(string $entityType): FieldMapperInterface
    {
        if (empty($this->fieldMapperEntity[$entityType])) {
            if (empty($entityType)) {
                // phpcs:ignore Magento2.Exceptions.DirectThrow
                throw new Exception('No entity type given');
            }
            if (!isset($this->fieldMappers[$entityType])) {
                throw new \LogicException('There is no such field mapper: ' . $entityType);
            }
            $fieldMapperClass = $this->fieldMappers[$entityType];
            $this->fieldMapperEntity[$entityType] = $this->objectManager->create($fieldMapperClass);
            if (!($this->fieldMapperEntity[$entityType] instanceof FieldMapperInterface)) {
                throw new \InvalidArgumentException(
                    'Field mapper must implement \MageOs\TypeSense\Model\Adapter\FieldMapperInterface'
                );
            }
        }

        return $this->fieldMapperEntity[$entityType];
    }
}
