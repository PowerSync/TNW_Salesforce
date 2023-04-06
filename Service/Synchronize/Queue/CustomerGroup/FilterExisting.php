<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Queue\CustomerGroup;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Service\Model\ResourceModel\Objects\MassLoadObjectIds;

class FilterExisting
{
    const KEY_FORMAT = '%s_%s';

    /** @var MassLoadObjectIds  */
    protected $massLoadObjectIds;

    public function __construct(
        MassLoadObjectIds $massLoadObjectIds
    ) {
        $this->massLoadObjectIds = $massLoadObjectIds;
    }

    /**
     * @param array $entityIds
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $entities, $websiteId): array
    {
        $groupByType = [];
        $result = [];

        foreach ($entities as $entity) {
            $createBy = 'CustomerGroup';

            switch (true) {
                case !empty($entity['group_id']):
                    $entityId = $entity['group_id'];

                    break;
                case !empty($entity['customer_group_id']):
                    $entityId = $entity['customer_group_id'];

                    break;
                case !empty($entity['entity_id']):
                    $entityId = $entity['entity_id'];

                    break;
                default:
                    $entityId = null;
            }

            if ($entityId) {
                $groupByType[$createBy][] = $entityId;
                $key = $this->getHash($createBy, $entityId);
                $result[$key] = $entity;
            }
        }

        foreach ($groupByType as $magentoType => $entityIds) {
            $salesforceIdsByEntity = $this->massLoadObjectIds->execute($entityIds, $magentoType, (int)$websiteId);
            foreach ($salesforceIdsByEntity as $entityId => $salesforceIds) {
                if (empty($salesforceIds) || empty($salesforceIds['tnw_mage_basic__Contact_Group__c'])) {
                    continue;
                }

                $websiteId = $salesforceIds['tnw_mage_basic__Contact_Group__c'];
                $key = $this->getHash($createBy, $entityId);

                if (!empty($websiteId)) {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }

    /**
     * @param $createBy
     * @param $entityId
     * @return string
     */
    public function getHash($createBy, $entityId)
    {
        return sprintf(self::KEY_FORMAT, $createBy, $entityId);
    }
}
