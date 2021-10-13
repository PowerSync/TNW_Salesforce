<?php
declare(strict_types=1);

namespace TNW\Salesforce\Block\System\Config\Form\Field\Salesforce;

class TestConnection extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Set template to itself
     *
     * @return \TNW\Salesforce\Block\System\Config\Form\Field\Salesforce\TestConnection
     */
    protected function _prepareLayout(): TestConnection
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/testconnection.phtml');
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element): string
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : __('Test Connection');
        $websiteParam = '';
        if($websiteId = $this->getRequest()->getParam('website')){
            $websiteParam = '/website/'.$websiteId;
        }
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('tnw_salesforce/system_config/testconnection'.$websiteParam),
            ]
        );

        return $this->_toHtml();
    }

}
