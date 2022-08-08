<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Block\System\Config\Form\Field;

use Magento\Backend\Block\Widget\Button;

/**
 *  Restart button element
 */
class RestartConsumersButton extends Button
{
    /**
     * @inheritDoc
     */
    protected function _beforeToHtml()
    {
        $this->setData('id', 'restart_consumers_button');
        $this->setData('name', 'restart_consumers_button');
        $this->setData('value', 'restart_consumers_button');
        $this->setData('label', __('Restart Consumers'));

        return parent::_beforeToHtml();
    }

    /**
     * @inheritDoc
     */
    public function getOnClick(): string
    {
        $url = $this->getUrl('tnw_salesforce/system_config/restartConsumers');

        return sprintf("location.href = '%s';", $url);
    }
}
