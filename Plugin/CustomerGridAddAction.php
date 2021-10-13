<?php
declare(strict_types=1);

namespace TNW\Salesforce\Plugin;

/**
 * Class CustomerGridAddAction
 * @package TNW\Salesforce\Plugin
 */
class CustomerGridAddAction
{
    /** @var  \Magento\Framework\UrlInterface */
    protected $urlBuilder;

    /**
     * CustomerGridAddAction constructor.
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param \Magento\Customer\Ui\Component\Listing\Column\Actions $subject
     * @param \Closure $proceed
     * @param array $dataSource
     * @return array
     */
    public function aroundPrepareDataSource(
        \Magento\Customer\Ui\Component\Listing\Column\Actions $subject,
        \Closure $proceed,
        array $dataSource
    ): array
    {
        $result = $proceed($dataSource);

        if (isset($result['data']['items'])) {
            foreach ($result['data']['items'] as &$item) {
                $item[$subject->getData('name')]['sf_sync'] = [
                    'href' => $this->urlBuilder->getUrl(
                        'tnw_salesforce/customer/synccustomer',
                        [
                            'customer_id' => $item['entity_id'],
                            'return' => 'index'
                        ]
                    ),
                    'label' => __('Sync with Salesforce'),
                    'hidden' => false,
                ];
            }
        }

        return $result;
    }
}
