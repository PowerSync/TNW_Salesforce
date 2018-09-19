<?php
namespace TNW\Salesforce\Block\Wizard\Step;

/**
 * Class Website
 * @package TNW\Salesforce\Block\Wizard\Step
 */
class Website extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Store\Model\WebsiteFactory|null
     */
    protected $websiteFactory = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->websiteFactory = $websiteFactory;
    }

    /**
     * Get Websites
     *
     * @return array
     */
    public function getWebsites()
    {
        return $this->websiteFactory->create()->getCollection()->toOptionArray();
    }

    /**
     * Get url to sync website
     *
     * @return array
     */
    public function getWebsiteSyncUrl()
    {
        return $this->getUrl('tnw_salesforce/wizard/websites');
    }
}
