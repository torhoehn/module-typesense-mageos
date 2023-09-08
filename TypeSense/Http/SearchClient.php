<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Http;

use Magento\AdvancedSearch\Model\Client\ClientInterface;
use MageOs\TypeSense\Builder\TypeSenseClientBuilder;

class SearchClient implements ClientInterface
{
    public function __construct(private readonly TypeSenseClientBuilder $clientBuilder)
    {
    }

    public function testConnection(): bool
    {
        try {
            // todo
            $this->clientBuilder->build()->health->retrieve();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
