<?php
namespace TNW\Salesforce\Block\Wizard\Step;

/**
 * Class Account
 * @package TNW\Salesforce\Block\Wizard\Step
 */
class Account extends \Magento\Backend\Block\Template
{
    /**
     * @var null|\TNW\Salesforce\Model\Config\Source\Customer\Owner
     */
    protected $accountModel = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \TNW\Salesforce\Model\Config\Source\Customer\Owner $accountModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \TNW\Salesforce\Model\Config\Source\Customer\Owner $accountModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->accountModel = $accountModel;
    }

    /**
     * Get owners
     *
     * @return array
     */
    public function getOwners()
    {
        return $this->accountModel->getOwners(true);
    }
}
