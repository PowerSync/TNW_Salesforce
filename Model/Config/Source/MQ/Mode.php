<?php


namespace TNW\Salesforce\Model\Config\Source\MQ;


class Mode implements \Magento\Framework\Data\OptionSourceInterface
{
    const AUTOMATIC = '0';
    const RABBIT_MQ = 'amqp';
    const MYSQL_MQ = 'db';

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AUTOMATIC,
                'label' => __('Detect MQ system automatically')
            ],
            [
                'value' => self::RABBIT_MQ,
                'label' => __('Prefer RabbitMQ')
            ],
            [
                'value' => self::MYSQL_MQ,
                'label' => __('Prefer MysqlMQ')
            ]
        ];
    }
}
