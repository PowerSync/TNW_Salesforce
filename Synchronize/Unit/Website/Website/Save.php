<?php
namespace TNW\Salesforce\Synchronize\Unit\Website\Website;

use TNW\Salesforce\Synchronize;

class Save extends Synchronize\Unit\UnitAbstract
{
    /**
     * @throws \RuntimeException
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \OutOfBoundsException
     */
    public function process()
    {
        $salesforceIds = [];
        foreach ($this->entities() as $entity) {
            if (null === $entity->getId()) {
                continue;
            }

            $resource = $entity->getResource();
            $connection = $resource->getConnection();

            $connection->update($resource->getMainTable(), [
                'salesforce_id' => $entity->getData('salesforce_id')
            ], $connection->quoteInto("{$resource->getIdFieldName()}=?", $entity->getId()));
            $salesforceIds[$entity->getId()] = $entity->getData('salesforce_id');
        }

        $this->group()->messageDebug("Save \"salesforce_id\":\n%s", $salesforceIds);
    }

    /**
     * @return \Magento\Store\Model\Website[]
     * @throws \OutOfBoundsException
     */
    protected function entities()
    {
        return $this->unit('websiteLoad')->get('entities');
    }
}