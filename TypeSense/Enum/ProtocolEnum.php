<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Enum;

enum ProtocolEnum: string
{
    case HTTP = 'http';
    case HTTPS = 'https';
}
