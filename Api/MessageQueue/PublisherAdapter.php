<?php

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
