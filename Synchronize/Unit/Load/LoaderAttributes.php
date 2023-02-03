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
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Product;
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
     * @param $magentoType
     * @param $collectionFactory
     * @param GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
     */
    public function __construct(
        string $magentoType,
        string $collectionFactoryName,
        ObjectManagerInterface                   $objectManager,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
    ) {
        $this->magentoType = $magentoType;
        $this->objectManager = $objectManager;
        $this->collectionFactory = $this->objectManager->create($collectionFactoryName);
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
    }

    /**
     * @param $entities
     * @return array
     */
    public function getEntityIds($entities)
    {
        $ids = [];

        foreach ($entities as $entity) {
            if (!$entity instanceof Product) {
                continue;
            }

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

        if (empty($entityIds)) {
            return $entities;
        }

        /** @var \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter($collection->getResource()->getEntityIdField(), $entityIds);

        $attributeCodesArray = $this->getMappedAttributeCodesByMagentoType->execute([$this->magentoType])[$this->magentoType] ?? [];

        foreach (array_chunk($attributeCodesArray, self::MAX_TABLES_JOIN) as $attributeCodes) {
            $attributeCollection = clone $collection;
            $attributeCollection->clear();
            $attributeCodes && $attributeCollection->addAttributeToSelect($attributeCodes, 'left');
            foreach ($attributeCollection as $customerWithAttributes) {
                foreach($entities as $entity) {
                    if ($customerWithAttributes->getId() == $entity->getId()) {
                        foreach ($customerWithAttributes->getData() as $attribute => $value) {
                            if (!in_array($attribute, array_keys($entity->getData()))) {
                                $entity->setData($attribute, $value);
                            }
                        }
                        break;
                    }
                }
            }
        }

        return $entities;
    }
}
