<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 *  Button field
 */
class ButtonField extends Field
{
    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $button = $this->getLayout()->createBlock('TNW\Salesforce\Block\System\Config\Form\Field\RestartConsumersButton');
        return $button->toHtml();
    }
}
