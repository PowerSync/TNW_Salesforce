<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Phrase;

interface IdentificationInterface
{
    /**
     * Print Entity
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return string|Phrase
     */
    public function printEntity($entity);
}
