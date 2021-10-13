<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

interface LoadLoaderInterface
{
    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy(): string;

    /**
     * Load
     *
     * @param int $entityId
     * @param array $additional
     * @return DataObject
     */
    public function load($entityId, array $additional): DataObject;
}
