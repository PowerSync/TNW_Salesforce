<?php

namespace TNW\Salesforce\Model\Config\Source\Salesforce;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Option\ArrayInterface;
use TNW\Salesforce\Synchronize\Transport\Calls\Query\Output;
use TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Base as SalesforceEntityRepository;

/**
 * Class Base
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class Base extends DataObject implements ArrayInterface
{
    /** @var ManagerInterface */
    protected $messageManager;

    /** @var SalesforceEntityRepository */
    protected $salesforceEntityRepository;

    /** @var bool */
    private $addEmptyFirstItem;

    /**
     * Owner constructor.
     *
     * @param ManagerInterface           $messageManager
     * @param SalesforceEntityRepository $salesforceEntityRepository
     * @param array                      $data
     * @param bool                       $addEmptyFirstItem
     */
    public function __construct(
        ManagerInterface $messageManager,
        SalesforceEntityRepository $salesforceEntityRepository,
        array $data = [],
        bool $addEmptyFirstItem = true
    ) {
        parent::__construct($data);

        $this->messageManager = $messageManager;
        $this->salesforceEntityRepository = $salesforceEntityRepository;
        $this->addEmptyFirstItem = $addEmptyFirstItem;
    }

    /**
     * @return array|Output
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
        $options = $entities = [];

        try {
            $entities = $this->getObjects();
            $this->addEmptyFirstItem && $options[''] = ' ';
            foreach ($entities as $data) {
                $options[$data['Id']] = $data['Name'];
            }
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        return $options;
    }
}
