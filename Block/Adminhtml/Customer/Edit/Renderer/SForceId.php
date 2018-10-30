<?php

namespace TNW\Salesforce\Block\Adminhtml\Customer\Edit\Renderer;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class SForceId extends \Magento\Framework\Data\Form\Element\Link
{
    /** @var \TNW\Salesforce\Client\Salesforce  */
    private $client;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        \TNW\Salesforce\Client\Salesforce $client,
        array $data
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
        $this->client = $client;
    }

    public function getElementHtml()
    {
        return $this->generateLinkToSalesforce($this->getValue());
    }

    /**
     * Generate link to specified object
     *
     * @param string $field
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function generateLinkToSalesforce($field)
    {
        $results = [];

        $url = $this->client->getSalesForceUrl($this->getData('website_id'));
        foreach (explode("\n", $field) as $value) {
            $currency = '';
            if (strpos($value, ':') !== false) {
                list($currency, $value) = explode(':', $value);
                $currency .= ': ';
            }

            if (empty($value)) {
                continue;
            }

            if ($url) {
                $results[] = sprintf(
                    '%1$s<a target="_blank" style="font-family:monospace;" href="%2$s/%3$s" title="%4$s">%3$s</a>',
                    $currency,
                    $url,
                    $value,
                    __('Show on Salesforce')
                );
            } else {
                $results[] = $value;
            }
        }

        return sprintf('<div class="control-value">%s</div>', implode('<br>', $results));
    }
}
