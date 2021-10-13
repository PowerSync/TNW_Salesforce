<?php
declare(strict_types=1);

namespace TNW\Salesforce\Client;

use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use TNW\Salesforce\Model\Customer\Config;

/**
 * Salesforce customer client
 *
 * Class Customer
 * @package TNW\Salesforce\Client
 *
 * @TODO: delete and test!
 */
class Customer extends Salesforce
{
    const CACHE_CUSTOMER_OWNERS_LIST = 'customer_owners';
    const SFORCE_CONTACT_OBJECT = 'Contact';
    const SFORCE_ACCOUNT_OBJECT = 'Account';

    /** @var Config */
    protected $config;

    /**
     * Customer constructor.
     *
     * @param Config $config
     * @param Collection $cacheCollection
     * @param State $cacheState
     * @param \TNW\Salesforce\Model\Logger $logger
     * @param \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector
     */
    public function __construct(
        Config $config,
        Collection $cacheCollection,
        State $cacheState,
        \TNW\Salesforce\Model\Logger $logger,
        \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector
    ) {
        parent::__construct($config, $cacheCollection, $cacheState, $logger, $websiteDetector);
        $this->config = $config;
    }

    /**
     * Get Connect client, connected to Salesforce
     *
     * @param int|null $websiteId
     * @return null|\Tnw\SoapClient\Client
     * @throws \Exception
     */
    public function getClient($websiteId = null): ?\Tnw\SoapClient\Client
    {
        if (!(bool)parent::getClientStatus($websiteId)) {
            throw new \Exception('Salesforce integration is DISABLED');
        }

        return parent::getClient($websiteId);
    }

    /**
     * Get Customer Integration status
     *
     * @param null $websiteId
     * @return bool
     */
    public function getClientStatus($websiteId = null): bool
    {
        return
            parent::getClientStatus($websiteId)
            && (bool)$this->config->getCustomerStatus($websiteId);
    }

    /**
     * Return Owners Request
     * @param bool|false $connectionChecked - set flag to retrieve owners,
     * if the salesforce sync was configured properly, but not activated
     * @return array|null
     * @throws \Exception
     */
    public function getOwners($connectionChecked = false): ?array
    {
        if ($cached = $this->loadCache(self::CACHE_CUSTOMER_OWNERS_LIST)) {
            return $cached;
        }

        $query = "SELECT Id, Name FROM User WHERE IsActive = true AND UserType != 'CsnOnly'";
        if ($connectionChecked) {
            $client = parent::getClient();
        } else {
            $client = $this->getClient();
        }
        $data = $client->query($query);
        $result = [];
        foreach ($data as $account) {
            $result[$account->Id] = $account->Name;
        }

        $this->saveCache($result, self::CACHE_CUSTOMER_OWNERS_LIST);

        return $result;
    }

    public function upsertContacts($key, $objects): array
    {
        return $this->upsertData($key, $objects, self::SFORCE_CONTACT_OBJECT);
    }

    public function upsertAccounts($key, $objects): array
    {
        return $this->upsertData($key, $objects, self::SFORCE_ACCOUNT_OBJECT);
    }

    /**
     * Get Salesforce Contact object name
     * @return string
     */
    public function salesforceContactObjectName(): string
    {
        return self::SFORCE_CONTACT_OBJECT;
    }

    /**
     * Get Salesforce Account object name
     * @return string
     */
    public function salesforceAccountObjectName(): string
    {
        return self::SFORCE_ACCOUNT_OBJECT;
    }
}
