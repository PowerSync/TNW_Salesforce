<?php
namespace TNW\Salesforce\Block\Adminhtml\System\Log;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class LoginButton implements ButtonProviderInterface
{
    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    private $context;

    /**
     * @var \TNW\Salesforce\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \TNW\Salesforce\Model\Config $config
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \TNW\Salesforce\Model\Config $config,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->context = $context;
        $this->config = $config;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getButtonData()
    {
        return [
            'label' => __('Login Salesforce'),
            'class_name' => \Magento\Backend\Block\Widget\Button\SplitButton::class,
            'options' => $this->loginUrlButtonOptions(),
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loginUrlButtonOptions()
    {
        $splitButtonOptions = [];
        foreach ($this->config->getWebsitesGrouppedByOrg() as $websiteId) {
            $splitButtonOptions[$websiteId] = [
                'label' => $this->websiteRepository->getById($websiteId)->getName(),
                'onclick' => "setLocation('{$this->getLoginUrl($websiteId)}')",
            ];
        }

        return $splitButtonOptions;
    }

    /**
     * Get URL for back (reset) button
     *
     * @param int $websiteId
     * @return string
     */
    public function getLoginUrl($websiteId)
    {
        return $this->context->getUrlBuilder()
            ->getUrl('tnw_salesforce/system_log/login', ['website_id' => $websiteId]);
    }
}
