<?php
declare(strict_types=1);

namespace MageOs\TypeSense\Model;

/**
 * The purpose of this class is adding the support for TypeSense
 */
class TypeSense extends SearchClient
{
    /**
     * Add mapping to OpenSearch index
     */
    public function addFieldsMapping(array $fields, string $index, string $entityType): void
    {
        $params = [
            'index' => $index,
            'body' => [
                'properties' => [],
                'dynamic_templates' => $this->dynamicTemplatesProvider->getTemplates(),
            ],
        ];

        foreach ($this->applyFieldsMappingPreprocessors($fields) as $field => $fieldInfo) {
            $params['body']['properties'][$field] = $fieldInfo;
        }

        $this->getTypeSenseClient()->indices()->putMapping($params);
    }

    /**
     * Execute search by $query
     *
     */
    public function query(array $query): array
    {
        unset($query['type']);
        return $this->getTypeSenseClient()->search($query);
    }
}
