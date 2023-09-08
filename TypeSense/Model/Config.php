<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Model;

use Magento\AdvancedSearch\Model\Client\ClientOptionsInterface;
use Magento\AdvancedSearch\Model\Client\ClientResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Store\Model\ScopeInterface;
use function in_array;

class Config implements ClientOptionsInterface {
    const ENGINE_NAME = 'typesense';

    private string $prefix;
    private array $engineList;

    /**
     * @param string|null $prefix
     * @param array $engineList
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        private ClientResolver $clientResolver,
        private EngineResolverInterface $engineResolver,
        $prefix = null,
        $engineList = []
    ) {
        $this->prefix = $prefix ?: $this->clientResolver->getCurrentEngine();
        $this->engineList = $engineList;
    }

    public function prepareClientOptions($options = []): array
    {
        $defaultOptions = [
            'hostname' => $this->getTypesenseConfigData('server_hostname'),
            'port' => $this->getTypesenseConfigData('server_port'),
            'index' => $this->getTypesenseConfigData('index_prefix'),
            'api_key' => $this->getTypesenseConfigData('api_key'),
        ];
        $options = array_merge($defaultOptions, $options);
        $allowedOptions = array_merge(array_keys($defaultOptions), ['engine']);

        $array_filter = [];
        foreach ($options as $key => $item) {
            if (in_array($key, $allowedOptions, true)) {
                $array_filter[$key] = $item;
            }
        }
        return $array_filter;
    }

    public function getTypesenseConfigData($field, $storeId = null)
    {
        return $this->getSearchConfigData($this->prefix . '_' . $field, $storeId);
    }

    public function getSearchConfigData($field, $storeId = null)
    {
        $path = 'catalog/search/' . $field;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isTypesenseEnabled(): bool
    {
        return in_array($this->engineResolver->getCurrentSearchEngine(), $this->engineList, true);
    }

    public function getIndexPrefix()
    {
        return $this->getTypesenseConfigData('index_prefix');
    }
}
