<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\DynamicTemplates;

class IntegerMapper implements MapperInterface
{
    /**
     * @inheritDoc
     */
    public function processTemplates(array $templates): array
    {
        $templates[] = [
            'integer_mapping' => [
                'match_mapping_type' => 'long',
                'mapping' => [
                    'type' => 'integer',
                ],
            ],
        ];

        return $templates;
    }
}
