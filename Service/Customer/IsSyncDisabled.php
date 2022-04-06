<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\Customer;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;

/**
 * Is customer sync disabled service.
 */
class IsSyncDisabled implements IsSyncDisabledInterface
{
    private const ATTRIBUTE_CODE = 'sforce_disable_sync';

    /** @var ResourceConnection */
    private $resourceConnection;

    /** @var Config */
    private $eavConfig;

    /** @var bool[] */
    private $cache;

    /**
     * @param ResourceConnection $resourceConnection
     * @param EavConfig          $eavConfig
     */
    public function __construct(ResourceConnection $resourceConnection, EavConfig $eavConfig)
    {
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $customerId): bool
    {
        if (isset($this->cache[$customerId])) {
            return $this->cache[$customerId];
        }

        $attribute = $this->eavConfig->getAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE);
        if (!$attribute || !$attribute->getId()) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $attribute->getBackendTable();
        $select = $connection->select()
            ->from($table, [new Expression('1')])
            ->where('entity_id = ?', $customerId)
            ->where('attribute_id = ?', $attribute->getId())
            ->where('value = 1');

        $this->cache[$customerId] = (bool)$connection->fetchOne($select);

        return $this->cache[$customerId];
    }
}
