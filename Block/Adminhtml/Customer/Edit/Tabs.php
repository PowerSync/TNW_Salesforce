<?php

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form\Generic;
use TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer\SForceId;
use TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer\SyncStatus;
use TNW\Salesforce\Model\Entity\SalesforceIdStorage;

class Tabs extends Generic implements TabInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Objects
     */
    private $resourceObjects;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        CustomerRepositoryInterface $customerRepository,
        \TNW\Salesforce\Model\ResourceModel\Objects $resourceObjects,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->customerRepository = $customerRepository;
        $this->resourceObjects = $resourceObjects;

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
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomer()
    {
        $customerId = $this->getCustomerId();
        return $this->customerRepository->getById($customerId);
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

    /**
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initForm()
    {
        if (!$this->canShowTab()) {
            return $this;
        }

        /**@var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('salesforce_');
        $form->setFieldNameSuffix('customer');

        $fieldSet = $form->addFieldset('base_fieldset', ['legend' => __('Salesforce')]);
        $fieldSet->addType('sync_status', SyncStatus::class);
        $fieldSet->addType('sforce_id', SForceId::class);

        $status = $this->resourceObjects->loadStatus(
            (int)$this->getCustomerId(),
            SalesforceIdStorage::MAGENTO_TYPE_CUSTOMER,
            (int)$this->websiteId()
        );

        $fieldSet->addField('sforce_sync_status', 'sync_status', [
            'name' => 'sforce_sync_status',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Sync Status'),
            'title' => __('Sync Status'),
            'value' => $status,
        ]);

        $salesforceIds = $this->resourceObjects->loadObjectIds(
            $this->getCustomerId(),
            SalesforceIdStorage::MAGENTO_TYPE_CUSTOMER,
            $this->websiteId()
        );

        $fieldSet->addField('sforce_id', 'sforce_id', [
            'name' => 'sforce_id',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Salesforce Contact Id'),
            'title' => __('Salesforce Contact Id'),
            'value' => isset($salesforceIds['Contact']) ? $salesforceIds['Contact'] : '',
            'website_id' => $this->websiteId(),
        ]);

        $fieldSet->addField('sforce_account_id', 'sforce_id', [
            'name' => 'sforce_account_id',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Salesforce Account Id'),
            'title' => __('Salesforce Account Id'),
            'value' => isset($salesforceIds['Account']) ? $salesforceIds['Account'] : '',
            'website_id' => $this->websiteId(),
        ]);

        $fieldSet->addField('sforce_lead_id', 'sforce_id', [
            'name' => 'sforce_lead_id',
            'data-form-part' => $this->getData('target_form'),
            'label' => __('Salesforce Lead Id'),
            'title' => __('Salesforce Lead Id'),
            'value' => isset($salesforceIds['Lead']) ? $salesforceIds['Lead'] : '',
            'website_id' => $this->websiteId(),
        ]);

        $this->additionalFields($fieldSet);

        // Sync button
        $fieldSet->addField('salesforce-sync-button', 'note', [
            'name' => 'salesforce-sync-button',
            'label' => '',
            'after_element_html' => $this->getSyncButtonHtml($this->getCustomerId())
        ]);

        $this->setForm($form);

        return $this;
    }

    /**
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function websiteId()
    {
        return $this->getCustomer()->getWebsiteId();
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
     *
     * @param $attributeCode
     *
     * @return \Magento\Framework\Api\AttributeInterface|mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getAttributeValue($attributeCode)
    {
        $value = $this->getCustomer()->getCustomAttribute($attributeCode);
        if ($value) {
            $value = $value->getValue();
        }

        return $value;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->initForm();
            return parent::_toHtml();
        }

        return '';
    }
}
