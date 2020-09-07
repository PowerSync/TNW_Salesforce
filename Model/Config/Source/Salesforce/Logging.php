<?php
namespace TNW\Salesforce\Model\Config\Source\Salesforce;

class Logging implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => '0', 'label' => __('Disable')],
            ['value' => '1', 'label' => __('File Log Only')],
            ['value' => '2', 'label' => __('Database Only')],
            ['value' => '3', 'label' => __('Database and File Logs')],
        ];

        return $options;
    }
}
