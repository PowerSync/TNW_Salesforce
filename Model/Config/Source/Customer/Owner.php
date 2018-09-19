<?php
namespace TNW\Salesforce\Model\Config\Source\Customer;

/**
 * Class Owner
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class Owner extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \TNW\Salesforce\Client\Salesforce
     */
    protected $client;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @param \TNW\Salesforce\Client\Customer $client
     * @param array $data
     */
    public function __construct(
        \TNW\Salesforce\Client\Customer $client,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        array $data = []
    ) {
        parent::__construct($data);

        $this->client = $client;
        $this->messageManager = $messageManager;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $owners = $this->client->getOwners();
            $options[''] = ' ';
            foreach($owners as $value => $label) {
                $options[$value] = $label;
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        return $options;
    }

    /**
     * Retrieve all owner options array
     * @param bool|false $connectionTested - set if connection to salesforce was tested, but sync wasn`t enabled
     * @return array
     */
    public function getOwners($connectionTested = false)
    {
        $owners = array();
        $options =array();

        try {
            $owners = $this->client->getOwners($connectionTested);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        foreach ($owners as $key => $value) {
            $options[] = array(
                'value' => $key,
                'label' => $value
            );
        }
        return $options;
    }
}
