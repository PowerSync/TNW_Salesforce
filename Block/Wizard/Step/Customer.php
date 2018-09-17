<?php
namespace TNW\Salesforce\Block\Wizard\Step;

/**
 * Class Customer
 * @package TNW\Salesforce\Block\Wizard\Step
 */
class Customer extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Customer\Model\Config\Source\Group\Multiselect|null
     */
    protected $customerGroupModel = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Model\Config\Source\Group\Multiselect $customerGroupModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\Config\Source\Group\Multiselect $customerGroupModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerGroupModel = $customerGroupModel;
    }

    /**
     * Get customer group
     *
     * @return array
     */
    public function getCustomerGroups()
    {
        return $this->customerGroupModel->toOptionArray();
    }
}
