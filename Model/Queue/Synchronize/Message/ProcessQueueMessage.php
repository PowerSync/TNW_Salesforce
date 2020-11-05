<?php

namespace TNW\Salesforce\Model\Queue\Synchronize\Message;

use TNW\Salesforce\Synchronize\Queue\Synchronize;

class ProcessQueueMessage
{
    protected $directoryList;

    protected $synchronizeEntity;

    protected $websiteEmulator;

    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        Synchronize $synchronizeEntity,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
    )
    {
        $this->synchronizeEntity = $synchronizeEntity;
        $this->websiteEmulator = $websiteEmulator;
        $this->directoryList = $directoryList;
        $logDir = $directoryList->getPath('log');
        $writer = new \Laminas\Log\Writer\Stream($logDir . DIRECTORY_SEPARATOR . 'realtime-consumer.log');
        $logger = new \Laminas\Log\Logger();
        $logger->addWriter($writer);
        $this->logger = $logger;
    }

    /**
     * process
     * @param $message
     * @return
     * @throws \Exception
     */
    public function process($message)
    {
        try{
//            $this->synchronize->synchronizeToWebsite($message);
            $this->websiteEmulator->wrapEmulationWebsite(
                [$this->synchronizeEntity, 'synchronizeToWebsite'],
                $message
            );
            $this->logger->info('SF process -' . serialize($this->synchronizeEntity));
        }catch (\Exception $e) {
            $this->logger->info('SF process Error ' . $e->getMessage());
        }
        return $message;
    }
}