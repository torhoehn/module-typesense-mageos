<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Model;

use Magento\AdvancedSearch\Model\Client\ClientInterface;
use Magento\Elasticsearch\Model\Adapter\FieldsMappingPreprocessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use MageOs\TypeSense\Builder\TypeSenseClientBuilder;
use MageOs\TypeSense\Data\NodeData;
use MageOs\TypeSense\Data\TypeSenseConfigData;
use MageOs\TypeSense\Data\TypeSenseConfigDataFactory;
use MageOs\TypeSense\Enum\ProtocolEnum;
use MageOs\TypeSense\SearchAdapter\DynamicTemplatesProvider;
use TypeSense\Client;

//use TypeSense\ClientBuilder;

class SearchClient implements ClientInterface
{
    private array $clientOptions;
    private array $client;
    private bool $pingResult = true;
    private array $fieldsMappingPreprocessors;

    /**
     * @var DynamicTemplatesProvider|null
     */
    public mixed $dynamicTemplatesProvider;
    private TypeSenseClientBuilder $clientBuilder;

    /**
     * @throws LocalizedException
     */
    public function __construct(
        $options = [],
        TypeSenseClientBuilder $clientBuilder,
        $fieldsMappingPreprocessors = [],
        ?DynamicTemplatesProvider $dynamicTemplatesProvider = null
    ) {
        if (empty($options['hostname']) || empty($options['api_key']) || empty($options['port'])) {
            throw new LocalizedException(
                __('The search failed because of a search engine misconfiguration.')
            );
        }
        $typeSenseConfig = new TypeSenseConfigData($options['api_key'], [
            new NodeData($options['hostname'], $options['port'], ProtocolEnum::HTTP)
        ]);
        $typeSenseClient = $clientBuilder->build($typeSenseConfig);
        $this->client[] = $typeSenseClient;

        // phpstan:ignore
        $this->client[getmypid()] = $typeSenseClient;
        $this->clientOptions = $options;
        $this->fieldsMappingPreprocessors = $fieldsMappingPreprocessors;
        $this->dynamicTemplatesProvider = $dynamicTemplatesProvider ?: ObjectManager::getInstance()
            ->get(DynamicTemplatesProvider::class);
    }

    /**
     * Execute suggest query for TypeSense
     */
    public function suggest(array $query): array
    {
        return $this->getTypeSenseClient()->suggest($query);
    }

    /**
     * Get TypeSense Client
     */
    public function getTypeSenseClient(): Client
    {
        $pid = getmypid();
        if (!isset($this->client[$pid])) {
            $config = $this->buildOSConfig($this->clientOptions);
            $this->client[$pid] = ClientBuilder::fromConfig($config, true);
        }
        return $this->client[$pid];
    }

    /**
     * Ping the client
     */
    public function ping(): bool
    {
        return $this->pingResult;
    }

    /**
     * Validate connection params for TypeSense
     */
    public function testConnection(): bool
    {
        return $this->ping();
    }

    /**
     * Build config for TypeSense
     */
    private function buildOSConfig(array $options = []): array
    {
        $hostname = preg_replace('/http[s]?:\/\//i', '', $options['hostname']);
        // @codingStandardsIgnoreStart
        $protocol = parse_url($options['hostname'], PHP_URL_SCHEME);
        // @codingStandardsIgnoreEnd
        if (!$protocol) {
            $protocol = 'http';
        }

        $authString = '';
        if (!empty($options['enableAuth']) && (int) $options['enableAuth'] === 1) {
            $authString = "{$options['username']}:{$options['password']}@";
        }

        $portString = '';
        if (!empty($options['port'])) {
            $portString = ':'.$options['port'];
        }

        $host = $protocol.'://'.$authString.$hostname.$portString;

        $options['hosts'] = [$host];

        return $options;
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
