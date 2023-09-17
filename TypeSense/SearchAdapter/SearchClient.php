<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

use Exception;
use Magento\AdvancedSearch\Model\Client\ClientInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use MageOs\TypeSense\Builder\TypeSenseClientBuilder;
use MageOs\TypeSense\Data\TypeSenseConfigData;
use MageOs\TypeSense\Exception\ConfigurationException;
use MageOs\TypeSense\Exception\InvalidConfigurationException;
use MageOs\TypeSense\Mapper\TypeSenseConfigDataMapper;
use TypeSense\Client;

class SearchClient implements ClientInterface
{
    /** @var Client[] */
    private array $client = [];
    private TypeSenseConfigData $typeSenseConfigData;

    /**
     * @throws LocalizedException
     */
    public function __construct(
        private readonly TypeSenseClientBuilder $clientBuilder,
        private readonly TypeSenseConfigDataMapper $typeSenseConfigDataMapper,
        protected ?DynamicTemplatesProvider $dynamicTemplatesProvider = null,
        private $fieldsMappingPreprocessors = [],
        $options = []
    ) {
        $pid = getmypid();
        if (!array_key_exists($pid, $this->client)) {
            if (empty($options['hostname']) || empty($options['api_key']) || empty($options['port'])) {
                throw new LocalizedException(
                    __('The search failed because of a search engine misconfiguration.')
                );
            }

            $typeSenseConfig = $this->typeSenseConfigDataMapper->map(
                hostname: $options['hostname'],
                apiKey: $options['api_key'],
                port: (int)$options['port']
            );

            $typeSenseClient = $clientBuilder->build($typeSenseConfig);

            // phpstan:ignore
            $this->client[getmypid()] = $typeSenseClient;
            $this->typeSenseConfigData = $typeSenseConfig;
        }

        $this->dynamicTemplatesProvider = $dynamicTemplatesProvider ?:
            ObjectManager::getInstance()->get(DynamicTemplatesProvider::class);
    }

    /**
     * Execute suggest query for TypeSense
     */
    public function suggest(array $query): array
    {
        return $this->getTypeSenseClient()->suggest($query);
    }

    /**
     * @throws ConfigurationException
     * @throws InvalidConfigurationException
     */
    public function getTypeSenseClient(): Client
    {
        $pid = getmypid();
        if (!array_key_exists($pid, $this->client)) {
            $this->client[$pid] = $this->clientBuilder->build($this->typeSenseConfigData);
        }
        return $this->client[$pid];
    }

    /**
     * @throws LocalizedException
     */
    public function ping(): bool
    {
        try {
            $result = $this->getTypeSenseClient()->getHealth()->retrieve();
            return $result['ok'] ?? false;
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Could not ping search engine: %1', $e->getMessage())
            );
        }
    }

    /**
     * Validate connection params for TypeSense
     * @throws Exception
     */
    public function testConnection(): bool
    {
        return $this->ping();
    }

    /**
     * Performs bulk query over OpenSearch  index
     */
    public function bulkQuery(array $query): void
    {
        $this->getTypeSenseClient()->bulk($query);
    }

    /**
     * Creates an OpenSearch index.
     */
    public function createIndex(string $index, array $settings): void
    {
        $this->getTypeSenseClient()->indices()->create(
            [
                'index' => $index,
                'body' => $settings,
            ]
        );
    }

    /**
     * Add/update an TypeSense index settings.
     *
     */
    public function putIndexSettings(string $index, array $settings): void
    {
        $this->getTypeSenseClient()->indices()->putSettings(
            [
                'index' => $index,
                'body' => $settings,
            ]
        );
    }

    /**
     * Delete an OpenSearch index.
     */
    public function deleteIndex(string $index): void
    {
        $this->getTypeSenseClient()->indices()->delete(['index' => $index]);
    }

    /**
     * Check if index is empty.
     */
    public function isEmptyIndex(string $index): bool
    {
        $stats = $this->getTypeSenseClient()->indices()->stats(['index' => $index, 'metric' => 'docs']);

        return $stats['indices'][$index]['primaries']['docs']['count'] === 0;
    }

    /**
     * Updates alias.
     */
    public function updateAlias(string $alias, string $newIndex, string $oldIndex = ''): void
    {
        $params = [
            'body' => [
                'actions' => [],
            ],
        ];
        if ($oldIndex) {
            $params['body']['actions'][] = ['remove' => ['alias' => $alias, 'index' => $oldIndex]];
        }
        if ($newIndex) {
            $params['body']['actions'][] = ['add' => ['alias' => $alias, 'index' => $newIndex]];
        }

        $this->getTypeSenseClient()->indices()->updateAliases($params);
    }

    public function indexExists(string $index): bool
    {
        return $this->getTypeSenseClient()->indices()->exists(['index' => $index]);
    }

    public function existsAlias(string $alias, string $index = ''): bool
    {
        $params = ['name' => $alias];
        if ($index) {
            $params['index'] = $index;
        }

        return $this->getTypeSenseClient()->indices()->existsAlias($params);
    }

    public function getAlias(string $alias): array
    {
        return $this->getTypeSenseClient()->indices()->getAlias(['name' => $alias]);
    }

    /**
     * Add mapping to TypeSense index
     *
     * @throws InvalidArgumentException
     */
    public function addFieldsMapping(array $fields, string $index, string $entityType): void
    {
        $params = [
            'index' => $index,
            'type' => $entityType,
            'include_type_name' => true,
            'body' => [
                $entityType => [
                    'properties' => [],
                    'dynamic_templates' => $this->dynamicTemplatesProvider->getTemplates(),
                ],
            ],
        ];

        foreach ($this->applyFieldsMappingPreprocessors($fields) as $field => $fieldInfo) {
            $params['body'][$entityType]['properties'][$field] = $fieldInfo;
        }

        $this->getTypeSenseClient()->indices()->putMapping($params);
    }

    public function query(array $query): array
    {
        return $this->getTypeSenseClient()->search($query);
    }

    public function getMapping(array $params): array
    {
        return $this->getTypeSenseClient()->indices()->getMapping($params);
    }

    public function deleteMapping(string $index, string $entityType): void
    {
        $this->getTypeSenseClient()->indices()->deleteMapping(
            [
                'index' => $index,
                'type' => $entityType,
            ]
        );
    }

    public function applyFieldsMappingPreprocessors(array $properties): array
    {
        foreach ($this->fieldsMappingPreprocessors as $preprocessor) {
            $properties = $preprocessor->process($properties);
        }
        return $properties;
    }
}
