<?php

declare(strict_types=1);

namespace MageOs\TypeSense\Indexer;

use MageOs\TypeSense\SearchAdapter\Adapter as TypeSenseAdapter;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Indexer\IndexStructureInterface;

class IndexStructure implements IndexStructureInterface
{
    public function __construct(
        private readonly TypeSenseAdapter $adapter,
        private readonly ScopeResolverInterface $scopeResolver
    ) {
    }

    public function create($indexerId, array $fields, array $dimensions = []): void
    {
        $dimension = array_shift($dimensions);
        $scopeId = (int)$this->scopeResolver->getScope($dimension->getValue())->getId();
        $this->adapter->checkIndex($scopeId, $indexerId, false);
    }

    public function delete($indexerId, array $dimensions = []): void
    {
        $dimension = array_shift($dimensions);
        $scopeId = (int)$this->scopeResolver->getScope($dimension->getValue())->getId();
        $this->adapter->cleanIndex($scopeId, $indexerId);
    }
}
