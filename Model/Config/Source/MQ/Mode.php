<?php


namespace TNW\Salesforce\Model\Config\Source\MQ;


class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    const RABBIT_MQ = 'amqp';
    const MYSQL_MQ = 'db';
    const AUTOMATIC = '1000';

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::RABBIT_MQ,
                'label' => __('RabbitMQ')
            ],
            [
                'value' => self::MYSQL_MQ,
                'label' => __('Standard')
            ]
        ];
    }
}
