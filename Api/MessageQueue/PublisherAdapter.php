<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\MessageQueue;
/**
 * Created for the Plugin only
 */
interface PublisherAdapter
{

    /**
     * @param $initTopicName
     * @return string
     */
    public function detectConnectionName($initTopicName);

    /**
     * @param $topicName
     * @return mixed
     */
    public function adaptTopic($topicName);

    /**
     * @param $topicName
     * @param $data
     */
    public function publish($topicName, $data);
}
