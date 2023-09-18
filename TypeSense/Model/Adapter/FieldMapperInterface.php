<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Model\Adapter;

interface FieldMapperInterface
{
    public const TYPE_QUERY = 'text';
    public const TYPE_SORT = 'sort';
    public const TYPE_FILTER = 'default';

    public function getFieldName(string $attributeCode, array $context = []): string;
    public function getAllAttributesTypes(array $context = []): array;
}
