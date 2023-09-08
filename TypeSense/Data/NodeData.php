<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Data;

use MageOs\TypeSense\Enum\ProtocolEnum;

readonly class NodeData
{
    public function __construct(
        public string $host,
        public int $port,
        public ProtocolEnum $protocol = ProtocolEnum::HTTP,
    ){
    }
}
