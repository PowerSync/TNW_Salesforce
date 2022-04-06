<?php
namespace TNW\Salesforce\Model\Config\Source\Customer;

use Magento\Framework\Message\ManagerInterface;
use TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Owner as OwnerRepository;
use TNW\Salesforce\Model\Config\Source\Salesforce\Base;

/**
 * Class Owner
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class Owner extends Base
{
    /**
     * Owner constructor.
     *
     * @param ManagerInterface $messageManager
     * @param OwnerRepository  $salesforceEntityRepository
     * @param array            $data
     * @param bool             $addEmptyFirstItem
     */
    public function __construct(
        ManagerInterface $messageManager,
        OwnerRepository $salesforceEntityRepository,
        array $data = [],
        bool $addEmptyFirstItem = false
    ) {
        parent::__construct($messageManager, $salesforceEntityRepository, $data, $addEmptyFirstItem);
    }
}
