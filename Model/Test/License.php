<?php
namespace TNW\Salesforce\Model\Test;

/**
 * Class License
 * @package TNW\Salesforce\Model\Test
 */
class License extends \TNW\Salesforce\Model\Test
{
    /**
     * @var string
     */
    protected $testLabel = 'Salesforce License';

    /**
     * @var null|\TNW\Salesforce\Model\Config
     */
    protected $config = null;

    /**
     * @var \TNW\Salesforce\Client\Salesforce
     */
    protected $clientSalesforce;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \TNW\Salesforce\Model\Config $config
     * @param \TNW\Salesforce\Client\Salesforce $clientSalesforce
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \TNW\Salesforce\Model\Config $config,
        \TNW\Salesforce\Client\Salesforce $clientSalesforce
    ) {
        parent::__construct($objectManager);
        $this->config = $config;
        $this->clientSalesforce = $clientSalesforce;
    }

    /**
     * @return License
     * @throws \Exception
     */
    public function execute()
    {
        $result = parent::STATUS_PASSED;

        try {
            /** @comment try to take object from our package */
            $salesforceWebsiteDescr = $this->clientSalesforce
                ->getClient()
                ->describeSObjects(array (\TNW\Salesforce\Client\Website::SFORCE_WEBSITE_OBJECT));
        } catch (\Exception $e) {
            $result = parent::STATUS_FAILED;
        }

        $this->setStatus($result);
        return $this;
    }
}
