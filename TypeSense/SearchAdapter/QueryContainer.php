<?php

declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

class QueryContainer
{
    public function __construct(private readonly array $query)
    {
    }

    public function getQuery(): array
    {
        return $this->query;
    }
}
