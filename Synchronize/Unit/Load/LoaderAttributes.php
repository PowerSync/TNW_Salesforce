<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Load;

use \Magento\Eav\Model\Entity\Collection\VersionControl\AbstractCollection as Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetMappedAttributeCodesByMagentoType;

/**
 * Load By Customer
 */
class LoaderAttributes
{
    const MAX_TABLES_JOIN = 50;

    /** @var string */
    private $magentoType;

    private $collectionFactory;

    /** @var GetMappedAttributeCodesByMagentoType */
    private $getMappedAttributeCodesByMagentoType;

    /**
     * @param string $magentoType
     * @param GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
     */
    public function __construct(
        $magentoType,
        $collectionFactory,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
    ) {
        $this->magentoType = $magentoType;
        $this->collectionFactory = $collectionFactory;
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
    }

    public function getEntityIds($entities)
    {
        $ids = [];

        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }

        $ids = array_filter($ids);

        return $ids;
    }

    /**
     * @param Collection $collection
     * @return AbstractDb|null
     * @throws LocalizedException
     */
    public function execute($entities): ?array
    {
        $entityIds = $this->getEntityIds($entities);

        /** @var \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('entity_id', $entityIds);

        $attributeCodesArray = $this->getMappedAttributeCodesByMagentoType->execute([$this->magentoType])[$this->magentoType] ?? [];

        foreach (array_chunk($attributeCodesArray, self::MAX_TABLES_JOIN) as $attributeCodes) {
            $attributeCollection = clone $collection;
            $attributeCollection->clear();
            $attributeCodes && $attributeCollection->addAttributeToSelect($attributeCodes, 'left');
            foreach ($attributeCollection as $customerWithAttributes) {
                if (!empty($entities[$customerWithAttributes->getId()])) {
                    $entities[$customerWithAttributes->getId()]->addData($customerWithAttributes->getData());
                }
            }
        }

        return $entities;
    }
}
