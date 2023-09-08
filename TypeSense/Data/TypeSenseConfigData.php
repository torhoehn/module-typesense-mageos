<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Data;

readonly class TypeSenseConfigData
{
    private const DEFAULT_CONNECTION_TIMEOUT = 2;

    /**
     * @param string $apiKey
     * @param NodeData[] $nodes
     * @param int $connectionTimeout
     */
    public function __construct(
       public string $apiKey,
       public array $nodes,
       public int $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT
    ) {
    }
}
