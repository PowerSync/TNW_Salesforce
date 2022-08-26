<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
