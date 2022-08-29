<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Block\Adminhtml\Config\Form\Field;

class Wsdl extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $browserLabel = __('Browse...');
        $saveLabel = __('Save');

        $html = <<<HTML
<span class="fileUpload">
    <input type="button" class="action action-primary" value="$browserLabel" />
    <input id="uploadBtn" type="file" class="upload" name="{$element->getData('name')}" style="width:100px;" />
</span>
<button title="$saveLabel" type="button" class="scalable" onclick="configForm.submit()" style="">
    <span><span><span>$saveLabel</span></span></span>
</button>
HTML;
        $element->addData(array(
            'after_element_html' => $html,
            'style' => 'width: 149px;'
        ));
        return sprintf('<div id="import_form" data-mage-init=\'{"TNW_Salesforce/js/browse":{}}\'>%s</div>', $element->getElementHtml());
    }
}
