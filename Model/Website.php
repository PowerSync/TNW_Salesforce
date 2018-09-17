<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Api\WebsiteInterface;
use Magento\Framework\DataObject;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class Website
 * @package TNW\Salesforce\Model
 */
class Website extends DataObject
{
    /**
     * @var null|\TNW\Salesforce\Client\Website
     */
    protected $websiteClient = null;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface|null
     */
    protected $websiteRepository = null;

    /**
     * @var null|Logger
     */
    protected $logger = null;

    /**
     * Website constructor.
     * @param WebsiteInterface $websiteClient
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        WebsiteInterface $websiteClient,
        WebsiteRepositoryInterface $websiteRepository,
        Logger $logger,
        array $data = []
    ) {
        parent::__construct($data);
        $this->websiteClient = $websiteClient;
        $this->websiteRepository = $websiteRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $websiteIds
     * @return array
     */
    public function syncWebsites($websiteIds = array())
    {
        $result = array();
        $websitesToSync = array();
        foreach ($websiteIds as $id) {
            $websitesToSync[$id] = $this->websiteRepository->getById($id);
            $result[] = array('id' => $id, 'status' => 'unsynced');
        }

        $syncErrors = array();
        try {
            $syncErrors = $this->websiteClient->syncWebsites($websitesToSync);
        } catch (\Exception $exception) {
            $this->logger->getLogger()->error($exception->getMessage());
            $syncErrors['exception'] = $exception->getMessage();
        }

        foreach ($result as $key => $value) {
            $value['errorMsg'] = ' - ';
            if ($syncErrors) {
                if (array_key_exists('exception', $syncErrors)) {
                    $value['status'] = 'unsynced';
                    $value['errorMsg'] = $syncErrors['exception'];
                    $result[$key] = $value;
                    continue;
                }
                if (array_key_exists($value['id'], $syncErrors)) {
                    $value['status'] = 'unsynced';
                    foreach ($syncErrors[$value['id']] as $error) {
                        $value['errorMsg'] .= $error->getMessage() . ';';
                    }
                    $result[$key] = $value;
                    continue;
                }
            }
            if ($this->websiteRepository->getById($value['id'])->getSalesforceId()) {
                $value['status'] = 'synced';
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
