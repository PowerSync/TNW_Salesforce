<?php
namespace TNW\Salesforce\Synchronize\Unit;

interface LoadLoaderInterface
{
    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy();

    /**
     * Load
     *
     * @param int $entityId
     * @param array $additional
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function load($entityId, array $additional);
}
