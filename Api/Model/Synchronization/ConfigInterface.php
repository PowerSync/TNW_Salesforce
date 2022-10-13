<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Model\Synchronization;

interface ConfigInterface
{
    const PRE_QUEUE_CRON = 7;

    const CLEAN_SYSTEM_LOGS = 8;

    /**
     * @param $websiteId
     * @return mixed
     */
    public function getSalesforceStatus($websiteId = null);

    /**
     * Get Max items count
     *
     * @param int|null $websiteId
     * @return int
     */
    public function getMaxItemsCountForQueue($websiteId = null);

    /**
     * @param $value
     * @param $type
     * @param $isCheck
     * @return mixed
     */
    public function setGlobalLastCronRun($value, $type = 0, $isCheck = false);


    /**
     * Get cron maximum attempt count to take response given the flag for additional attempts
     * @param bool $additionalAttempts
     * @return int
     */
    public function getMaxAdditionalAttemptsCount($additionalAttempts = false);

}
