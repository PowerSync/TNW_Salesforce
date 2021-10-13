<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel\Objects;

class ObjectIdSelect extends SelectAbstract
{
    /**
     * @var string
     */
    private $magentoType;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var string
     */
    private $entityIdField;

    /**
     * ObjectIdSelectBuilder constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param string $magentoType
     * @param string $salesforceType
     * @param string $entityIdField
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        $magentoType,
        $salesforceType,
        $entityIdField = 'main_table.entity_id',
        $connectionName = null
    ) {
        parent::__construct($resource, $connectionName);
        $this->magentoType = $magentoType;
        $this->salesforceType = $salesforceType;
        $this->entityIdField = $entityIdField;
    }

    /**
     * @inheritdoc
     */
    public function build()
    {
        return $this->select()
            ->from(['object' => $this->getTable('tnw_salesforce_objects')], ['object_id'])
            ->where("object.entity_id = {$this->entityIdField}")
            ->where('object.magento_type = ?', $this->magentoType)
            ->where('object.salesforce_type = ?', $this->salesforceType)
            ->where('object.store_id = 0')
            ->where('object.website_id IN(sf_website_id, 0)')
            ->order('object.website_id DESC')
            ->limit(1);
    }
}
