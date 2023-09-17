<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

use Magento\AdvancedSearch\Model\Client\ClientInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use MageOs\TypeSense\Model\Config;
use MageOs\TypeSense\Proxy\ClientFactoryProxy;
use Psr\Log\LoggerInterface;

class ConnectionManager
{
    protected ?ClientInterface $client = null;

    public function __construct(
        private readonly ClientFactoryProxy $clientFactory,
        private readonly Config             $clientConfig,
        private readonly LoggerInterface    $logger
    ){
    }

    /**
     * @throws LocalizedException
     */
    public function getConnection(array $options = []): ClientInterface
    {
        if (!$this->client) {
            $this->connect($options);
        }

        return $this->client;
    }

    /**
     * @throws LocalizedException
     */
    private function connect(array $options): void
    {
        try {
            $options = $this->clientConfig->prepareClientOptions($options);
            $this->client = $this->clientFactory->create($options);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new LocalizedException(new Phrase('Search client is not set.'));
        }
    }
}
