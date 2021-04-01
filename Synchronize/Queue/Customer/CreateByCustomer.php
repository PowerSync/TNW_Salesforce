<?php
namespace TNW\Salesforce\Synchronize\Queue\Customer;

use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\UrlInterface;
use TNW\Salesforce\Model\Queue;

/**
 * Create By Customer
 */
class CreateByCustomer implements \TNW\Salesforce\Synchronize\Queue\CreateInterface
{
    const CREATE_BY = 'customer';

    const LOAD_BY = 'customer';

    /**
     * @var Customer
     */
    private $resourceCustomer;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * CreateByCustomer constructor.
     * @param Customer $resourceCustomer
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Customer $resourceCustomer,
        UrlInterface $urlBuilder
    ) {
        $this->resourceCustomer = $resourceCustomer;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Create By
     *
     * @return string
     */
    public function createBy()
    {
        return static::CREATE_BY;
    }

    /**
     * Process
     *
     * @param int[] $entityIds
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     * @return Queue[]
     */
    public function process(array $entityIds, array $additional, callable $create, $websiteId)
    {
        $queues = [];
        foreach ($this->entities($entityIds) as $entity) {
            $customer = $entity['firstname'] . ' ' . $entity['lastname'];
            $customer_url = $this->urlBuilder->getUrl('customer/index/edit', ['id' => $entity['entity_id']]);

            if (isset($entity['company'])) {
                $company = $entity['company'];
                $company_url = $this->urlBuilder->getUrl('company/index/edit', ['id' => $entity['company_id']]);
            } else {
                $company = $customer;
                $company_url = $customer_url;
            }

            $queues[] = $create(
                static::LOAD_BY,
                $entity['entity_id'],
                $entity['base_entity_id'],
                [
                    'customer' => $customer,
                    'customer_url' => $customer_url,
                    'company' => $company,
                    'company_url' => $company_url,
                ]
            );
        }

        return $queues;
    }

    /**
     * Entities
     *
     * @param int[] $entityIds
     * @return array
     */
    public function entities(array $entityIds)
    {
        $connection = $this->resourceCustomer->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceCustomer->getEntityTable(),
                [
                    'entity_id' => $this->resourceCustomer->getEntityIdField(),
                    'base_entity_id' => $this->resourceCustomer->getEntityIdField(),
                    'firstname',
                    'lastname',
                ]
            )
            ->where($connection->prepareSqlCondition(
                $this->resourceCustomer->getEntityIdField(),
                ['in' => $entityIds]
            ));

        return $connection->fetchAll($select);
    }
}
