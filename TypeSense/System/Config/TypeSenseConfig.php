<?php

declare(strict_types=1);

namespace MageOs\TypeSense\System\Config;

use Magento\Store\Model\ScopeInterface;
use MageOs\TypeSense\Enum\ProtocolEnum;

class TypeSenseConfig
{
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function getServerHostName(): string
    {
        return $this->scopeConfig->getValue('catalog/search/typesense_server_hostname', ScopeInterface::SCOPE_STORE);
    }

    public function getServerPort(): string
    {
        return $this->scopeConfig->getValue('catalog/search/typesense_server_port', ScopeInterface::SCOPE_STORE);
    }

    public function getIndexPrefix(): string
    {
        return $this->scopeConfig->getValue('catalog/search/typesense_index_prefix', ScopeInterface::SCOPE_STORE);
    }

    public function getApiKey(): string
    {
        return $this->scopeConfig->getValue('catalog/search/typesense_api_key', ScopeInterface::SCOPE_STORE);
    }

    public function getServerProtocol(): ProtocolEnum
    {
        // todo
    }
}
