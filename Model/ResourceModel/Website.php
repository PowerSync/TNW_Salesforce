<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel;

class Website
{
    /**
     * Save Salesforce data for Website
     *
     * @param \Magento\Store\Model\Website $object
     */
    public function saveSalesforceId(\Magento\Store\Model\Website $object)
    {
        $resourceModel = $object->getResource();

        $table = $resourceModel->getMainTable();
        $bind = ['salesforce_id' => $object->getSalesforceId()];
        $where = ['code = ?' =>  $object->getCode()];

        $resourceModel->getConnection()->update($table, $bind, $where);
    }
}
