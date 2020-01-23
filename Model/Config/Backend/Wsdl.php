<?php
namespace TNW\Salesforce\Model\Config\Backend;

use Exception;
use Magento\Config\Model\Config\Backend\File;
use Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Xml\Parser;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use TNW\Salesforce\Model\Config\WebsiteDetector;

class Wsdl extends File
{
    const CACHE_TAG = 'tnw_salesforce_client';

    const USE_BULK_API_VERSION = 'tnwsforce_general/synchronization/use_bulk_api_version';

    const USE_BULK_API_VERSION_CACHE_IDENTIFIER = 'use_bulk_api_version';

    const API_URL = 'https://login.salesforce.com/services/Soap/c/';

    /** @var Parser */
    protected $xmlParser;

    /** @var Config */
    protected $resourceConfig;

    /** @var WebsiteDetector  */
    protected $websiteDetector;

    /** @var Collection  */
    protected $cacheCollection;

    /** @var  State */
    protected $cacheState;

    /** @var  array */
    protected $handCache = [];

    /**
     * The tail part of directory path for uploading
     *
     */
    const UPLOAD_DIR = 'wsdl';

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        UploaderFactory $uploaderFactory,
        RequestDataInterface $requestData,
        Filesystem $filesystem,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Parser $xmlParser,
        Config $resourceConfig,
        WebsiteDetector $websiteDetector,
        Collection $cacheCollection,
        State $cacheState,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $uploaderFactory,
            $requestData,
            $filesystem,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        $this->xmlParser = $xmlParser;

        $this->resourceConfig = $resourceConfig;

        $this->websiteDetector = $websiteDetector;

        $this->cacheCollection = $cacheCollection;

        $this->cacheState = $cacheState;
    }

    /**
     * Save uploaded file before saving config value
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $file = $this->getFileData();
        if (!empty($file)) {
            $uploadDir = $this->_getUploadDir();
            try {
                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);

                $this->saveApiVersion($result);
            } catch (Exception $e) {
                throw new LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $this->getFilePath($result);

            $this->setValue("{var}/$filename");
        } else {
            $this->setValue($value[0]);
        }

        return $this;
    }

    /**
     * @param $result
     * @return string
     */
    public function getFilePath($result)
    {
        return $this->_mediaDirectory->getRelativePath("{$result['path']}/{$result['file']}");

    }

    /**
     * @param $filePathInfo
     * @throws LocalizedException
     */
    public function saveApiVersion($filePathInfo)
    {

        $loadedxmlfile = $filePathInfo['path'] . '/' . $filePathInfo['file'];

        /** Disable external entity loading to prevent possible vulnerability */
        $previousLoaderState = libxml_disable_entity_loader(true);

        libxml_disable_entity_loader($previousLoaderState);

        $parsedXMLData = $this->xmlParser->load($loadedxmlfile)->xmlToArray();

        if (isset($parsedXMLData['definitions']['_value']['service'])) {
            $serviceNode = $parsedXMLData['definitions']['_value']['service'];
            $apiVersion = $this->getAPIVersion($serviceNode);

            if ($apiVersion) {
                $this->resourceConfig->saveConfig(
                    'tnwsforce_general/synchronization/use_bulk_api_version',
                    $apiVersion,
                    'default',
                    0
                );
                $websiteId = $this->websiteDetector->detectCurrentWebsite();
                $this->saveCache($apiVersion, self::USE_BULK_API_VERSION_CACHE_IDENTIFIER, $websiteId);
            }
        }
    }

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    }

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    public function getUploadDir()
    {
        return $this->_getUploadDir();
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * @param $serviceNode
     * @return mixed
     */
    public function getAPIVersion($serviceNode)
    {
        // https://login.salesforce.com/services/Soap/c/46.0/0DF1p000000HDYU
        $location = $this->arrayFindValueByKey($serviceNode, 'location');
        $locationArray = explode('/', $location);

        $apiversion = $locationArray[6];

        return $apiversion;
    }

    /**
     * @param $array
     * @param $search
     * @param array $keys
     * @return string|array
     */
    public function arrayFindValueByKey($array, $search, $keys = [])
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $sub = $this->arrayFindValueByKey($value, $search, array_merge($keys, [$key]));
                if (is_scalar($sub) || count($sub)) {
                    return $sub;
                }
            } elseif ($key === $search) {
                return $value;
            }
        }

        return [];
    }

    /**
     * Save cache
     *
     * @param String $value
     * @param String $identifier
     * @param int|null $websiteId
     *
     * @throws LocalizedException
     */
    protected function saveCache($value, $identifier)
    {
        $websiteId = $this->websiteDetector->detectCurrentWebsite();

        if ($this->cacheState->isEnabled(Collection::TYPE_IDENTIFIER)) {

            /** @var mixed $serialized */
            $serialized = serialize($value);

            $this->cacheCollection->save(
                $serialized,
                self::CACHE_TAG . $identifier
            );
        } else {
            $this->handCache[$identifier] = $value;
        }
    }
}
