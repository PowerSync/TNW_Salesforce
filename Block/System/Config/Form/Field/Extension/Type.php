<?php
declare(strict_types=1);

namespace TNW\Salesforce\Block\System\Config\Form\Field\Extension;

class Type extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement  $element): string
    {
        $element->setReadonly(1);

        return $element->setValue(__('Basic'))->getElementHtml();
    }
}
