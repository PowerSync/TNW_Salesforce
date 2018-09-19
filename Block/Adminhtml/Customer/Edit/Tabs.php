<?php

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;

class Tabs extends Generic implements TabInterface
{
    /** @var \Magento\Framework\Registry */
    protected $coreRegistry;
    /** @var CustomerRepositoryInterface */
    protected $customerRepository;
    /** @var bool|\Magento\Customer\Api\Data\CustomerInterface */
    protected $customer = false;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->customerRepository = $customerRepository;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Get current customer ID
     * @return string|null
     */
    protected function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * Get current customer object
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface|null
     */
    protected function getCustomer()
    {
        if ($this->customer === false) {
            $customerId = $this->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            if ($customer && !$customer->getId()) {
                $customer = null;
            }
            $this->customer = $customer;
        }

        return $this->customer;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Salesforce');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Salesforce');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    /**
     * Tab class getter
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    public function initForm()
    {
        if (!$this->canShowTab()) {
            return $this;
        }
        /**@var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('salesforce_');
        $form->setFieldNameSuffix('customer');

        /** @var  $fieldSet */
        $fieldSet = $form->addFieldset('base_fieldset', ['legend' => __('Salesforce')]);

        $fieldSet->addType(
            'sync_status',
            'TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer\SyncStatus'
        );

        $fieldSet->addType(
            'sforce_id',
            'TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer\SForceId'
        );
        
        $fieldSet->addField(
            'sforce_sync_status',
            'sync_status',
            [
                'name' => 'sforce_sync_status',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Sync Status'),
                'title' => __('Sync Status'),
                'value' => $this->getAttributeValue('sforce_sync_status'),
            ]
        );

        $fieldSet->addField(
            'sforce_id',
            'sforce_id',
            [
                'name' => 'sforce_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Salesforce Contact Id'),
                'title' => __('Salesforce Contact Id'),
                'value' => $this->getAttributeValue('sforce_id'),
            ]
        );

        $fieldSet->addField(
            'sforce_account_id',
            'sforce_id',
            [
                'name' => 'sforce_account_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Salesforce Account Id'),
                'title' => __('Salesforce Account Id'),
                'value' => $this->getAttributeValue('sforce_account_id'),
            ]
        );

        $fieldSet->addField(
            'sforce_lead_id',
            'sforce_id',
            [
                'name' => 'sforce_lead_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Salesforce Lead Id'),
                'title' => __('Salesforce Lead Id'),
                'value' => $this->getAttributeValue('sforce_lead_id'),
            ]
        );

        $this->additionalFields($fieldSet);

        // Sync button
        $fieldSet->addField(
            'salesforce-sync-button',
            'note',
            [
                'name' => 'salesforce-sync-button',
                'label' => '',
                'after_element_html' => $this->getSyncButtonHtml($this->getCustomerId())
            ]
        );

        $this->setForm($form);

        return $this;
    }

    /**
     * Generate Sync button
     * @param $productId
     * @return string
     */
    private function getSyncButtonHtml($productId)
    {
        $buttonData = $this->getSyncButtonData($productId);
        $template = <<<EOT
            <div class="customer-salesforce-sync">
            <button type="button" class="%s" onclick="%s">
                <span>%s</span>
            </button>
            </div>
EOT;
        $html = sprintf(
            $template,
            $buttonData['class'],
            $buttonData['on_click'],
            $buttonData['label']
        );

        return $html;
    }

    /**
     * Get sync button attributes
     * @param $customerId
     * @return array
     */
    private function getSyncButtonData($customerId)
    {
        $controller = $this->getUrl(
            'tnw_salesforce/customer/synccustomer',
            ['customer_id' => $customerId]
        );

        $data = [
            'label' => __('Sync Customer'),
            'on_click' => sprintf("location.href = '%s';", $controller),
            'class' => 'sf_sync_customer action-primary'
        ];

        return $data;
    }

    /**
     * Additional fields generation
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldSet
     */
    protected function additionalFields($fieldSet)
    {
        // Will be implemented in extended versions of salesforce connector
    }

    /**
     * Get value by customer attribute code
     * @param $attributeCode
     * @return \Magento\Framework\Api\AttributeInterface|mixed|null
     */
    protected function getAttributeValue($attributeCode)
    {
        $value = null;
        $customer = $this->getCustomer();
        if ($customer) {
            $value = $customer->getCustomAttribute($attributeCode);
            if ($value) {
                $value = $value->getValue();
            }
        }

        return $value;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->initForm();
            return parent::_toHtml();
        } else {
            return '';
        }
    }

//    /**
//     * Prepare the layout.
//     *
//     * @return $this
//     */
//    // You can call other Block also by using this function if you want to add phtml file.
//    public function getFormHtml()
//    {
//        $html = parent::getFormHtml();
//        return $html;
//
//        $html .= $this->getLayout()->createBlock(
//            'Webkul\CustomerEdit\Block\Adminhtml\Customer\Edit\Tab\EdditionalBlock'
//        )->toHtml();
//        return $html;
//    }
}