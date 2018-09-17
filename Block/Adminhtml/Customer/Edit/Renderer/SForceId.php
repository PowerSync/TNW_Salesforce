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
        parent::__construct($factoryElement, $factoryCollection, $escaper,
            $data);
        $this->client = $client;
    }

    public function getElementHtml()
    {
        return $this->generateLinkToSalesforce($this->getValue());
    }

    /**
     * Generate link to specified object
     *
     * @param $field salesforceId or string type1:salesforceId1;type2:salesforceId2
     * @return string
     */
    protected function generateLinkToSalesforce($field)
    {
        $result = null;
        $results = [];
        if ($field) {
            $valuesArray = explode("\n", $field);

            $url = $this->client->getSalesForceUrl();
            foreach ($valuesArray as $value) {
                $currency = '';
                if (strpos($value, ':') !== false) {
                    $tmp = explode(':', $value);
                    $currency = $tmp[0] . ': ';
                    $field = $tmp[1];
                    $value = $tmp[1];
                }

                if (empty($value)) {
                    continue;
                }

                if ($url) {
                    $results[] = sprintf('%s<a target="_blank" href="%s" title="%s">%s</a>',
                        $currency,
                        $url . '/' . $value,
                        __('Show on Salesforce'),
                        $field
                    );
                } else {
                    $results[] = $value;
                }
            }
        }

        return
            '<div class="control-value">'
            . implode('<br/>', $results)
            . '</div>';
    }
}
