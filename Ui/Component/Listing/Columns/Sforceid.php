<?php
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Sforceid extends Column
{
    /**
     * @var \TNW\Salesforce\Client\Salesforce
     */
    protected $client;

    /**
     * @var \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool
     */
    private $dividerPool;

    /**
     * @var string
     */
    private $groupName;

    /**
     * @var string
     */
    private $entityIdName;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \TNW\Salesforce\Client\Salesforce $client
     * @param \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool $dividerPool
     * @param string $groupName
     * @param string $entityIdName
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \TNW\Salesforce\Client\Salesforce $client,
        \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool $dividerPool,
        $groupName,
        $entityIdName,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->client = $client;
        $this->dividerPool = $dividerPool;
        $this->groupName = $groupName;
        $this->entityIdName = $entityIdName;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $entityGroup = $this->dividerPool
            ->getDividerByGroupCode($this->groupName)
            ->process(array_map([$this, 'entityId'], $dataSource['data']['items']));

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item[$fieldName])) {
                continue;
            }

            foreach ($entityGroup as $websiteId => $entities) {
                if (!isset($entities[$this->entityId($item)])) {
                    continue;
                }

                $item["{$fieldName}_html"] = $this->generateLinkToSalesforce($item[$fieldName], $websiteId);
                continue 2;
            }
        }

        return $dataSource;
    }

    /**
     * @param array $items
     *
     * @return mixed
     */
    private function entityId(array &$items)
    {
        return $items[$this->entityIdName];
    }

    /**
     * Generate link to specified object
     *
     * @param string $field
     * @param int $websiteId
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateLinkToSalesforce($field, $websiteId)
    {
        $result = [];

        $url = $this->client->getSalesForceUrl($websiteId);
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
                $result[] = sprintf(
                    '%1$s<a target="_blank" href="%2$s/%3$s" title="%4$s">%3$s</a>',
                    $currency,
                    $url,
                    $value,
                    __('Show on Salesforce')
                );
            } else {
                $result[] = sprintf('%s', $value);
            }
        }


        return implode('<br>', $result);
    }
}
