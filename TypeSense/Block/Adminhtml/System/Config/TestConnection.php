<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Block\Adminhtml\System\Config;

class TestConnection extends \Magento\AdvancedSearch\Block\Adminhtml\System\Config\TestConnection
{
    protected function _getFieldMapping(): array
    {
        $fields = [
            'hostname' => 'catalog_search_typesense_server_hostname',
            'port' => 'catalog_search_typesense_server_port',
            'index' => 'catalog_search_typesense_index_prefix',
            'api_key' => 'catalog_search_typesense_api_key',
        ];

        return array_merge(parent::_getFieldMapping(), $fields);
    }
}
