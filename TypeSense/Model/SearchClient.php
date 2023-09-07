<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Model;

use Magento\AdvancedSearch\Model\Client\ClientInterface;
use Magento\Elasticsearch\Model\Adapter\FieldsMappingPreprocessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\OpenSearch\Model\Adapter\DynamicTemplatesProvider;
use OpenSearch\Client;
use OpenSearch\ClientBuilder;

class SearchClient implements ClientInterface
{
    private array $clientOptions;
    private array $client;
    private bool $pingResult;
    private array $fieldsMappingPreprocessors;
    /**
     * @var DynamicTemplatesProvider|null
     */
    public mixed $dynamicTemplatesProvider;

    /**
     * @throws LocalizedException
     */
    public function __construct(
        $options = [],
        $openSearchClient = null,
        $fieldsMappingPreprocessors = [],
        ?DynamicTemplatesProvider $dynamicTemplatesProvider = null
    ) {
        if (empty($options['hostname'])
            || ((!empty($options['enableAuth']) && ($options['enableAuth'] == 1))
                && (empty($options['username']) || empty($options['password'])))
        ) {
            throw new LocalizedException(
                __('The search failed because of a search engine misconfiguration.')
            );
        }
        // phpstan:ignore
        if ($openSearchClient instanceof Client) {
            $this->client[getmypid()] = $openSearchClient;
        }
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
     * Get OS Client
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
     * Validate connection params for OpenSearch
     */
    public function testConnection(): bool
    {
        return $this->ping();
    }

    /**
     * Build config for OpenSearch
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
        if (!empty($options['enableAuth']) && (int)$options['enableAuth'] === 1) {
            $authString = "{$options['username']}:{$options['password']}@";
        }

        $portString = '';
        if (!empty($options['port'])) {
            $portString = ':' . $options['port'];
        }

        $host = $protocol . '://' . $authString . $hostname . $portString;

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
     * Add/update an Elasticsearch index settings.
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
     *
     */
    public function isEmptyIndex(string $index): bool
    {
        $stats = $this->getTypeSenseClient()->indices()->stats(['index' => $index, 'metric' => 'docs']);
        if ($stats['indices'][$index]['primaries']['docs']['count'] === 0) {
            return true;
        }

        return false;
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

    /**
     * Checks whether OpenSearch index exists
     *
     */
    public function indexExists(string $index): bool
    {
        return $this->getTypeSenseClient()->indices()->exists(['index' => $index]);
    }

    /**
     * Exists alias.
     *
     */
    public function existsAlias(string $alias, string $index = ''): bool
    {
        $params = ['name' => $alias];
        if ($index) {
            $params['index'] = $index;
        }

        return $this->getTypeSenseClient()->indices()->existsAlias($params);
    }

    /**
     * Get alias.
     *
     */
    public function getAlias(string $alias): array
    {
        return $this->getTypeSenseClient()->indices()->getAlias(['name' => $alias]);
    }

    /**
     * Add mapping to OpenSearch index
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

    /**
     * Execute search by $query
     *
     */
    public function query(array $query): array
    {
        return $this->getTypeSenseClient()->search($query);
    }

    /**
     * Get mapping from Elasticsearch index.
     *
     */
    public function getMapping(array $params): array
    {
        return $this->getTypeSenseClient()->indices()->getMapping($params);
    }

    /**
     * Delete mapping in OpenSearch index
     *
     */
    public function deleteMapping(string $index, string $entityType): void
    {
        $this->getTypeSenseClient()->indices()->deleteMapping(
            [
                'index' => $index,
                'type' => $entityType,
            ]
        );
    }

    /**
     * Apply fields mapping preprocessors
     *
     */
    public function applyFieldsMappingPreprocessors(array $properties): array
    {
        foreach ($this->fieldsMappingPreprocessors as $preprocessor) {
            $properties = $preprocessor->process($properties);
        }
        return $properties;
    }
}
