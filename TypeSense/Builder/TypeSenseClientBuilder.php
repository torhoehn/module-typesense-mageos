<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Builder;

use MageOs\TypeSense\Data\TypeSenseConfigData;
use Typesense\Client;
use Typesense\Exceptions\ConfigError;
use MageOs\TypeSense\Exception\InvalidConfigurationException;
use MageOs\TypeSense\Exception\ConfigurationException;
use Magento\Framework\Phrase;

class TypeSenseClientBuilder
{
    /**
     * @throws ConfigurationException
     * @throws InvalidConfigurationException
     */
    public function build(TypeSenseConfigData $typeSenseConfigData): Client
    {
        $data = [
            'api_key' => $typeSenseConfigData->apiKey,
            'connection_timeout_seconds' => $typeSenseConfigData->connectionTimeout,
        ];

        if (!$typeSenseConfigData->nodes) {
            throw new InvalidConfigurationException(new Phrase(__('No nodes defined in the configuration')));
        }

        foreach ($typeSenseConfigData->nodes as $node) {
            $data['nodes'][] = [
                'host' => $node->host,
                'port' => $node->port,
                'protocol' => $node->protocol->value,
            ];
        }

        try {
            return new Client($data);
        } catch (ConfigError $e) {
            throw new ConfigurationException('Invalid configuration: ' . $e->getMessage());
        }
    }
}
