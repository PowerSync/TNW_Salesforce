<?php
namespace TNW\Salesforce\Block\System\Config\Form\Field\Extension;

class BulkApi extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement  $element)
    {
        $element->setReadonly(1);
        
        return $element->getElementHtml();
    }
}
