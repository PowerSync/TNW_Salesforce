<?php
namespace TNW\Salesforce\Synchronize\Unit\Website;

use TNW\Salesforce\Synchronize;

class Identification implements Synchronize\Unit\IdentificationInterface
{
    /**
     * @param \Magento\Store\Model\Website $entity
     * @return string
     */
    public function printEntity($entity)
    {
        return sprintf('Website, Code "%d"', $entity->getCode());
    }
}