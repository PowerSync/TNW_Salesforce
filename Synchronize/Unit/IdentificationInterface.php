<?php
namespace TNW\Salesforce\Synchronize\Unit;

interface IdentificationInterface
{
    /**
     * Print Entity
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return string
     */
    public function printEntity($entity);
}
