<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Map;

use Magento\Framework\DB\Select;
use TNW\Salesforce\Model\ResourceModel\Mapper\CollectionFactory;

/**
 *  Get fields with "Skip blank values" is "No"
 */
class GetFieldsWithDisableSkipBlankValues
{
    /** @var array  */
    private $processed = [];

    /** @var array  */
    private $cache = [];

    /** @var CollectionFactory */
    private $collectionFactory;

    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get fields with "Skip blank values" is "No"
     *
     * @param array $objectTypes
     *
     * @return array
     */
    public function execute(array $objectTypes): array
    {
        if(!$objectTypes) {
            return [];
        }

        $objectTypes = array_unique($objectTypes);
        $missedObjectTypes = [];
        foreach ($objectTypes as $objectType) {
            if(!isset($this->processed[$objectType])) {
                $this->processed[$objectType] = 1;
                $missedObjectTypes[] = $objectType;
            }
        }

        if ($missedObjectTypes) {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('object_type', ['in' => $objectTypes]);
            $collection->addFieldToFilter('skip_blank_values', ['eq' => 0]);
            $select = $collection->getSelect();
            $select->reset(Select::COLUMNS);
            $select->columns(
                [
                    'salesforce_attribute_name',
                    'object_type',
                ]
            );
            $items = $collection->getConnection()->fetchPairs($select);
            $items = $items ?: [];
            foreach ($items as $salesforceAttributeName => $objectType) {
                $this->cache[$objectType][$salesforceAttributeName] = $salesforceAttributeName;
            }

        }


        $result = [];
        foreach ($objectTypes as $objectType) {
            $result[$objectType] = $this->cache[$objectType] ?? [];
        }


        return $result;
    }
}
