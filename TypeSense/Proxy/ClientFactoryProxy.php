<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Proxy;

use Magento\AdvancedSearch\Model\Client\ClientFactoryInterface;
use Magento\AdvancedSearch\Model\Client\ClientInterface;
use Magento\AdvancedSearch\Model\Client\ClientResolver;

class ClientFactoryProxy implements ClientFactoryInterface
{
    /**
     * @param ClientResolver $clientResolver
     * @param ClientFactoryInterface[] $clientFactories
     */
    public function __construct(
        private readonly ClientResolver $clientResolver,
        private readonly array $clientFactories
    ) {
    }

    public function create(array $options = []): ClientInterface
    {
        $engine = $this->clientResolver->getCurrentEngine();
        $clientFactory = $this->clientFactories[$engine] ?? null;

        if ($clientFactory === null) {
            throw new \RuntimeException("The search client for the engine $engine is not set.");
        }

        return $clientFactory->create($options);
    }
}
