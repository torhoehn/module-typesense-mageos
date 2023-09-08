<?php
declare(strict_types=1);

namespace MageOs\TypeSense\SearchAdapter;

use Magento\Framework\Exception\InvalidArgumentException;
use MageOs\TypeSense\SearchAdapter\DynamicTemplates\MapperInterface;

/**
 * Dynamic templates' provider for search engines.
 */
class DynamicTemplatesProvider
{
    /**
     * @param MapperInterface[] $mappers
     */
    public function __construct(private array $mappers)
    {
    }

    /**
     * Get Search Engine dynamic templates.
     *
     * @throws InvalidArgumentException
     */
    public function getTemplates(): array
    {
        $templates = [];
        foreach ($this->mappers as $mapper) {
            if (!$mapper instanceof MapperInterface) {
                throw new InvalidArgumentException(
                    __('Mapper %1 should implement %2', get_class($mapper), MapperInterface::class)
                );
            }
            $templates = $mapper->processTemplates($templates);
        }

        return $templates;
    }
}
