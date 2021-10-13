<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

class Identification implements IdentificationInterface
{
    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return string
     */
    public function printEntity($entity): string
    {
        return sprintf('class "%s", Id "%d"', get_class($entity), $entity->getId());
    }
}
