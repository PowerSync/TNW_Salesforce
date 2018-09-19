<?php
namespace TNW\Salesforce\Indexer\Handler;

use Magento\Framework\App\ResourceConnection\SourceProviderInterface;
use Magento\Framework\Indexer\HandlerInterface;

class Attribute implements HandlerInterface
{

    /**
     * @param SourceProviderInterface $source
     * @param string $alias
     * @param array $fieldInfo
     */
    public function prepareSql(SourceProviderInterface $source, $alias, $fieldInfo)
    {
        $source->addFieldToSelect($fieldInfo['origin'], $fieldInfo['name']);
    }
}
