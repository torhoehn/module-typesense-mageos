<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Mapper;

use MageOs\TypeSense\Data\NodeData;
use MageOs\TypeSense\Data\TypeSenseConfigData;
use MageOs\TypeSense\Enum\ProtocolEnum;

class TypeSenseConfigDataMapper
{
    public function map(string $hostname, string $apiKey, int $port): TypeSenseConfigData
    {
        $nodes = [
            new NodeData(host: $hostname, port: $port, protocol: ProtocolEnum::HTTP)
        ];

        return new TypeSenseConfigData(apiKey: $apiKey, nodes: $nodes);
    }
}
