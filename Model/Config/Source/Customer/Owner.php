<?php
namespace TNW\Salesforce\Model\Config\Source\Customer;

use TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Owner as SalesforceOwnerRepository;
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
     * @var \TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Base
     */
    private $salesforceOwnerRepository;

    /**
     * Owner constructor.
     * @param \TNW\Salesforce\Client\Customer $client
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param $salesforceOwnerRepository $salesforceRepository
     * @param array $data
     */
    public function __construct(
        \TNW\Salesforce\Client\Customer $client,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        SalesforceOwnerRepository $salesforceOwnerRepository,
        array $data = []
    ) {
        parent::__construct($data);

        $this->client = $client;
        $this->messageManager = $messageManager;
        $this->salesforceOwnerRepository = $salesforceOwnerRepository;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray($connectionTested = false)
    {
        $options = $owners= [];

        try {
            $owners = $this->salesforceOwnerRepository->search();
            $options[''] = ' ';
            foreach($owners as $data) {
                $options[$data['Id']] = $data['Name'];
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
            $owners = $this->toOptionArray($connectionTested);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        foreach ($owners as $value => $label) {

            if (empty($value)) {
                continue;
            }

            $options[] = array(
                'value' => $value,
                'label' => $label
            );
        }
        return $options;
    }
}
