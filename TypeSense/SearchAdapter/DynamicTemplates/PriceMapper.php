<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\DynamicTemplates;

class PriceMapper implements MapperInterface
{
    /**
     * @inheritDoc
     */
    public function processTemplates(array $templates): array
    {
        $templates[] = [
            'price_mapping' => [
                'match' => 'price_*',
                'match_mapping_type' => 'string',
                'mapping' => [
                    'type' => 'double',
                    'store' => true,
                ],
            ],
        ];

        return $templates;
    }
}
