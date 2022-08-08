<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 *  Restart button element
 */
class RestartConsumersButton extends Button
{
    /**
     * @param Context                 $context
     * @param array                   $data
     * @param Random|null             $random
     * @param SecureHtmlRenderer|null $htmlRenderer
     */
    public function __construct(
        Context $context,
        array $data = [],
        ?Random $random = null,
        ?SecureHtmlRenderer $htmlRenderer = null
    ) {
        $data['id'] = 'restart_consumers_button';
        $data['name'] = 'restart_consumers_button';
        $data['value'] = 'restart_consumers_button';
        $data['label'] = __('Restart Consumers');

        parent::__construct($context, $data, $random, $htmlRenderer);
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
