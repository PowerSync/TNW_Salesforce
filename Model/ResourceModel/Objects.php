<?php
namespace TNW\Salesforce\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Objects extends AbstractDb
{
    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectObjectId;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectObjectIds;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectEntityId;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectEntityIds;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectEntityIdsByType;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectStatus;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $selectPriceBookId;

    /**
     * @var \TNW\Salesforce\Model\Config
     */
    private $config;

    /**
     * Objects constructor.
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \TNW\Salesforce\Model\Config $config
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \TNW\Salesforce\Model\Config $config,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->config = $config;
    }

    /**
     * @throws LocalizedException
     */
    protected function _construct()
    {
        $this->_init('tnw_salesforce_objects', 'row_id');

        $this->selectObjectId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['object_id', 'salesforce_type'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id')
            ->where('website_id IN (:entity_website_id, :base_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :entity_website_id, :base_website_id)'))
            ->limit(1);

        $this->selectObjectIds = $this->getConnection()->select()
            ->from($this->getMainTable(), ['object_id', 'salesforce_type'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id')
            ->where('website_id IN (:base_website_id, :entity_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :base_website_id, :entity_website_id)'));

        $this->selectEntityId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id', 'magento_type'])
            ->where('salesforce_type = :salesforce_type')
            ->where('object_id = :object_id')
            ->where('website_id IN (:entity_website_id, :base_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :entity_website_id, :base_website_id)'))
            ->limit(1);

        $this->selectEntityIds = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id', 'magento_type'])
            ->where('salesforce_type = :salesforce_type')
            ->where('object_id = :object_id')
            ->where('website_id IN (:entity_website_id, :base_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :base_website_id, :entity_website_id)'));

        $this->selectEntityIdsByType = $this->getConnection()->select()
            ->from($this->getMainTable(), ['entity_id'])
            ->where('magento_type = :magento_type')
            ->where('salesforce_type = :salesforce_type')
            ->where('object_id = :object_id')
            ->where('website_id IN (:entity_website_id, :base_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :base_website_id, :entity_website_id)'));

        $this->selectStatus = $this->getConnection()->select()
            ->from($this->getMainTable(), ['status'])
            ->where('magento_type = :magento_type')
            ->where('entity_id = :entity_id')
            ->where('website_id IN(:entity_website_id, :base_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :entity_website_id, :base_website_id)'))
            ->limit(1);

        $this->selectPriceBookId = $this->getConnection()->select()
            ->from($this->getMainTable(), ['object_id', 'salesforce_type'])
            ->where('magento_type = "PricebookEntry"')
            ->where('entity_id = :entity_id')
            ->where('store_id = :store_id')
            ->where('website_id IN(:entity_website_id, :base_website_id)')
            ->order(new \Zend_Db_Expr('FIELD(website_id, :entity_website_id, :base_website_id)'))
            ->limit(1);
    }

    /**
     * @param $websiteId
     *
     * @return int
     */
    public function baseWebsiteId($websiteId)
    {
        return $this->config->baseWebsiteIdLogin($websiteId);
    }

    /**
     * @param string $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @return string
     */
    public function loadObjectId($entityId, $magentoType, $websiteId)
    {
        return $this->getConnection()->fetchOne($this->selectObjectId, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId,
            'entity_website_id' => (int)$websiteId,
            'base_website_id' => (int)$this->baseWebsiteId($websiteId),
        ]);
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $websiteId
     *
     * @return string
     */
    public function loadPriceBookId($productId, $storeId, $websiteId)
    {
        $condition = [
            'entity_id' => $productId,
            'store_id' => $storeId,
            'entity_website_id' => $websiteId,
            'base_website_id' => $this->baseWebsiteId($websiteId),
        ];

        return $this->getConnection()->fetchOne($this->selectPriceBookId, $condition);
    }

    /**
     * @param string $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @return array
     */
    public function loadObjectIds($entityId, $magentoType, $websiteId)
    {
        $ids = $this->getConnection()->fetchPairs($this->selectObjectIds, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId,
            'entity_website_id' => $websiteId,
            'base_website_id' => $this->baseWebsiteId($websiteId),
        ]);
        $result = [];
        foreach ($ids as $id => $type) {
            if (!isset($result[$type])) {
                $result[$type] = $id;
            } else {
                $result[$type] .= "\n" . $id;
            }
        }

        return $result;
    }

    /**
     * @param string $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @return int
     */
    public function loadStatus($entityId, $magentoType, $websiteId)
    {
        return $this->getConnection()->fetchOne($this->selectStatus, [
            'magento_type' => $magentoType,
            'entity_id' => $entityId,
            'entity_website_id' => $websiteId,
            'base_website_id' => $this->baseWebsiteId($websiteId),
        ]);
    }

    /**
     * @param string $objectId
     * @param string $salesforceType
     * @param int $websiteId
     *
     * @return int
     */
    public function loadEntityId($objectId, $salesforceType, $websiteId)
    {
        return $this->getConnection()->fetchOne($this->selectEntityId, [
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId,
            'entity_website_id' => $websiteId,
            'base_website_id' => $this->baseWebsiteId($websiteId),
        ]);
    }

    /**
     * @param string $objectId
     * @param string $salesforceType
     * @param int $websiteId
     *
     * @return array
     */
    public function loadEntityIds($objectId, $salesforceType, $websiteId)
    {
        return array_flip($this->getConnection()->fetchPairs($this->selectEntityId, [
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId,
            'entity_website_id' => $websiteId,
            'base_website_id' => $this->baseWebsiteId($websiteId),
        ]));
    }

    /**
     * @param array $records
     *
     * @throws LocalizedException
     */
    public function saveRecords(array $records)
    {
        if (empty($records)) {
            return;
        }

        $records = array_map(function (array $record) {
            return array_intersect_key($record, array_flip([
                'magento_type',
                'entity_id',
                'object_id',
                'salesforce_type',
                'status',
                'website_id',
                'store_id',
                'additional'
            ]));
        }, $records);

        $this->getConnection()
            ->insertOnDuplicate($this->getMainTable(), $records);
    }

    /**
     * @param string $entityId
     * @param string $magentoType
     * @param int $status
     * @param int $websiteId
     *
     * @throws LocalizedException
     */
    public function saveStatus($entityId, $magentoType, $status, $websiteId)
    {
        $this->getConnection()
            ->update(
                $this->getMainTable(),
                ['status' => (int)$status],
                "entity_id = '$entityId' AND magento_type = '$magentoType' AND website_id = {$websiteId}"
            );
    }

    /**
     * @param string $entityId
     * @param string $magentoType
     * @param int $websiteId
     *
     * @throws LocalizedException
     */
    public function setPendingStatus($entityId, $magentoType, $websiteId)
    {
        $this->getConnection()
            ->update(
                $this->getMainTable(),
                ['status' => new \Zend_Db_Expr('(status + 10)')],
                new \Zend_Db_Expr(sprintf(
                    'entity_id = \'%s\' AND magento_type = \'%s\' AND website_id = %d AND status < 10',
                    $entityId,
                    $magentoType,
                    $websiteId
                ))
            );
    }

    /**
     * @param string $entityId
     * @param string $magentoType
     * @param int $websiteId
     * @param string|null $salesforceType
     *
     * @throws LocalizedException
     */
    public function unsetPendingStatus($entityId, $magentoType, $websiteId, $salesforceType = null)
    {
        $whereCondition = 'entity_id = %d AND magento_type = \'%s\' AND website_id = %d AND status > 9';
        if ($salesforceType) {
            $whereCondition .= ' AND salesforce_type = \'%s\'';
            $where = new \Zend_Db_Expr(sprintf($whereCondition, $entityId, $magentoType, $websiteId, $salesforceType));
        } else {
            $where = new \Zend_Db_Expr(sprintf($whereCondition, $entityId, $magentoType, $websiteId));
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            ['status' => new \Zend_Db_Expr('status - 10')],
            $where
        );
    }

    /**
     * @param string $objectId
     * @param string $magentoType
     * @param string $salesforceType
     * @param int $websiteId
     *
     * @return array
     */
    public function loadEntityIdsByType($objectId, $magentoType, $salesforceType, $websiteId)
    {
        return $this->getConnection()->fetchCol($this->selectEntityIdsByType, [
            'magento_type' => $magentoType,
            'salesforce_type' => $salesforceType,
            'object_id' => $objectId,
            'entity_website_id' => $websiteId,
            'base_website_id' => $this->baseWebsiteId($websiteId),
        ]);
    }
}
