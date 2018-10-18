<?php
namespace TNW\Salesforce\Model\Config\Source\Salesforce;

use TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Base as salesforceEntityRepository;
/**
 * Class Base
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class Base extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Base
     */
    protected $salesforceEntityRepository;

    /**
     * Owner constructor.
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param $salesforceEntityRepository $salesforceEntityRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        salesforceEntityRepository $salesforceEntityRepository,
        array $data = []
    ) {
        parent::__construct($data);

        $this->messageManager = $messageManager;
        $this->salesforceEntityRepository = $salesforceEntityRepository;
    }

    /**
     * @return \TNW\Salesforce\Synchronize\Transport\Calls\Query\Output
     */
    public function getObjects()
    {
        return $this->salesforceEntityRepository->search();
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $entities= [];

        try {
            $entities = $this->getObjects();
            $options[''] = ' ';
            foreach($entities as $data) {
                $options[$data['Id']] = $data['Name'];
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        return $options;
    }
}
