<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\FieldMapper\Product\FieldProvider\FieldName\Resolver;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeAdapter;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface;

class DefaultResolver implements ResolverInterface
{
    public function __construct(private ResolverInterface $baseResolver)
    {
    }

    /**
     * Returns field name.
     *
     * @param array $context
     */
    public function getFieldName(AttributeAdapter $attribute, $context = []): ?string
    {
        $fieldName = $this->baseResolver->getFieldName($attribute, $context);
        if ($fieldName === '_all') {
            $fieldName = '_search';
        }

        return $fieldName;
    }
}
