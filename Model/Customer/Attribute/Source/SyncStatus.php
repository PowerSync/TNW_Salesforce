<?php
namespace TNW\Salesforce\Model\Customer\Attribute\Source;

/**
 * Class SyncStatus
 * @package TNW\Salesforce\Model\Customer\Attribute\Source
 * @deprecated use TNW\Salesforce\Model\Objects\Status\Options instead
 */
class SyncStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * Value for synced state
     */
    const VALUE_SYNCED = 1;

    /**
     * Value for unsynced state
     */
    const VALUE_UNSYNCED = 0;

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('In Sync'), 'value' => self::VALUE_SYNCED],
                ['label' => __('Out of Sync'), 'value' => self::VALUE_UNSYNCED],
            ];
        }
        return $this->_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|int $value
     * @return string|false
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}
