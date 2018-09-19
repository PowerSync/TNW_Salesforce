<?php
namespace TNW\Salesforce\Synchronize\Unit;

class Identification implements IdentificationInterface
{
    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return string
     */
    public function printEntity($entity)
    {
        return sprintf('class "%s", Id "%d"', get_class($entity), $entity->getId());
    }
}