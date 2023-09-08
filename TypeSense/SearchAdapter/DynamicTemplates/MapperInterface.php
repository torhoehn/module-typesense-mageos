<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter\DynamicTemplates;

interface MapperInterface
{
    /**
     * Add/remove/edit dynamic template mapping.
     *
     * @param array $templates
     * @return array
     */
    public function processTemplates(array $templates): array;
}
