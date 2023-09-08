<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Model;

use Magento\AdvancedSearch\Model\Client\ClientOptionsInterface;
use Magento\AdvancedSearch\Model\Client\ClientResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Search\EngineResolverInterface;
use Magento\Store\Model\ScopeInterface;

class Config implements ClientOptionsInterface {
    const ENGINE_NAME = 'typesense';

    protected $scopeConfig;

    private $prefix;

    private $clientResolver;

    private $engineResolver;

    private $engineList;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ClientResolver $clientResolver
     * @param EngineResolverInterface $engineResolver
     * @param string|null $prefix
     * @param array $engineList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ClientResolver $clientResolver,
        EngineResolverInterface $engineResolver,
        $prefix = null,
        $engineList = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->clientResolver = $clientResolver;
        $this->engineResolver = $engineResolver;
        $this->prefix = $prefix ?: $this->clientResolver->getCurrentEngine();
        $this->engineList = $engineList;
    }

    public function prepareClientOptions($options = [])
    {
        $defaultOptions = [
            'hostname' => $this->getTypesenseConfigData('server_hostname'),
            'port' => $this->getTypesenseConfigData('server_port'),
            'index' => $this->getTypesenseConfigData('index_prefix'),
            'api_key' => $this->getTypesenseConfigData('api_key'),
        ];
        $options = array_merge($defaultOptions, $options);
        $allowedOptions = array_merge(array_keys($defaultOptions), ['engine']);

        return array_filter(
            $options,
            function (string $key) use ($allowedOptions) {
                return in_array($key, $allowedOptions);
            },
            ARRAY_FILTER_USE_KEY
        );
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

    public function isTypesenseEnabled()
    {
        return in_array($this->engineResolver->getCurrentSearchEngine(), $this->engineList);
    }

    public function getIndexPrefix()
    {
        return $this->getTypesenseConfigData('index_prefix');
    }
}
