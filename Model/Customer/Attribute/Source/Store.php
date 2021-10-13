<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\Customer\Attribute\Source;

/**
 * Workaround for Magento bug, see https://github.com/magento/magento2/pull/26475
 *
 * Class Store
 * @package TNW\Salesforce\Model\Customer\Attribute\Source
 */
class Store extends \Magento\Customer\Model\Customer\Attribute\Source\Store
{

    /**
     * @param string $value
     * @return array|string
     */
    public function getOptionText($value)
    {
        if (!$value) {
            $value = '0';
        }
        $isMultiple = false;
        if (strpos($value, ',') !== false) {
            $isMultiple = true;
            $value = explode(',', $value);
        }

        if (!$this->_options) {
            $collection = $this->_createStoresCollection();
            if ('store_id' == $this->getAttribute()->getAttributeCode()) {
                $collection->setWithoutDefaultFilter();
            }
            $this->_options = $collection->load()->toOptionArray();
            if ('created_in' == $this->getAttribute()->getAttributeCode()) {
                array_unshift($this->_options, ['value' => '0', 'label' => __('Admin')]);
            }
        }

        if ($isMultiple) {
            $values = [];
            foreach ($this->_options as $item) {
                if (in_array($item['value'], $value)) {
                    $values[] = $item['label'];
                }
            }
            return $values;
        }

        foreach ($this->_options as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }

        return false;
    }

}
