<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\DynamicTemplates;

class PositionMapper implements MapperInterface
{
    /**
     * @inheritDoc
     */
    public function processTemplates(array $templates): array
    {
        $templates[] = [
            'position_mapping' => [
                'match' => 'position_*',
                'match_mapping_type' => 'string',
                'mapping' => [
                    'type' => 'integer',
                    'index' => true,
                ],
            ],
        ];

        return $templates;
    }
}
