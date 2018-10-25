<?php
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Sforceid extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /** @var \TNW\Salesforce\Client\Salesforce  */
    protected $client;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \TNW\Salesforce\Client\Salesforce $client,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->client = $client;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $html = '';
                if (isset($item[$fieldName])) {
                    $value = trim($item[$fieldName]);
                    $html = $value;
                    if (strlen($value) > 0) {
                        $link = $this->generateLinkToSalesforce($value);
                        if ($link) {
                            $html = $link;
                        }
                    }
                }
                $item[$fieldName . '_html'] = $html;
            }
        }

        return $dataSource;
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
                    $result .= sprintf('%s<a target="_blank" href="%s" title="%s">%s</a><br />',
                        $currency,
                        $url . '/' . $value,
                        __('Show on Salesforce'),
                        $field
                    );
                } else {
                    $result .= sprintf('%s<br/>', $value);
                }
            }
        }

        return $result;
    }
}
