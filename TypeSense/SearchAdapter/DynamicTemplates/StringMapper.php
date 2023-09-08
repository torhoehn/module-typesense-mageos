<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\DynamicTemplates;

class StringMapper implements MapperInterface
{
    /**
     * @inheritDoc
     */
    public function processTemplates(array $templates): array
    {
        $templates[] = [
            'string_mapping' => [
                'match' => '*',
                'match_mapping_type' => 'string',
                'mapping' => [
                    'type' => 'text',
                    'index' => true,
                    'copy_to' => '_search',
                ],
            ],
        ];

        return $templates;
    }
}
