<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Website;

use TNW\Salesforce\Synchronize;

/**
 * Identification
 */
class Identification implements Synchronize\Unit\IdentificationInterface
{
    /**
     * Print Entity
     *
     * @param \Magento\Store\Model\Website $entity
     * @return string
     */
    public function printEntity($entity): string
    {
        return sprintf('website (code: %s)', $entity->getCode());
    }
}
