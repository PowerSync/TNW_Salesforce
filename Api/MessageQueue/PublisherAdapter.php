<?php
declare(strict_types=1);

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
    public function detectConnectionName($initTopicName): string;

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
