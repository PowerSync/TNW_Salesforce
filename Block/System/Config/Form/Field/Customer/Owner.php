<?php

namespace TNW\Salesforce\Block\System\Config\Form\Field\Customer;

class Owner extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

       //  $element->setReadonly(1);

       // return '<div style="color:darkred;">Salesforce integration is DISABLED</div>';
        return parent::_getElementHtml($element);
    }

}
