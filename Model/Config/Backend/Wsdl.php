<?php
namespace TNW\Salesforce\Model\Config\Backend;

use Magento\MediaStorage\Model\File\Uploader;
use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;
use TNW\Salesforce\Model\Config\WebsiteDetector;

class Wsdl extends \Magento\Config\Model\Config\Backend\File
{
    const CACHE_TAG = 'tnw_salesforce_client';

    const USE_BULK_API_VERSION = 'tnwsforce_general/synchronization/use_bulk_api_version';

    const USE_BULK_API_VERSION_CACHE_IDENTIFIER = 'use_bulk_api_version';

    /** @var \Magento\Framework\Xml\Parser */
    protected $xmlParser;

    /** @var \Magento\Config\Model\ResourceModel\Config */
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
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Xml\Parser $xmlParser,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        WebsiteDetector $websiteDetector,
        Collection $cacheCollection,
        State $cacheState,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $uploaderFactory,
            $requestData, $filesystem, $resource, $resourceCollection, $data);

        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);

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
     * @throws \Magento\Framework\Exception\LocalizedException
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


                $loadedxmlfile = $result['path'].'/'.$result['file'];
           
                /** Disable external entity loading to prevent possible vulnerability */
                $previousLoaderState = libxml_disable_entity_loader(true);

                libxml_disable_entity_loader($previousLoaderState);

                $parsedXMLData = $this->xmlParser->load($loadedxmlfile)->xmlToArray();

                if(isset($parsedXMLData['definitions']['_value']['service'])){
                    $serviceNode = $parsedXMLData['definitions']['_value']['service'];
                    $apiVersion = $this->getAPIVersion($serviceNode);

                    if($apiVersion){
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

            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__('%1', $e->getMessage()));
            }

            $filename = $this->_mediaDirectory->getRelativePath("{$result['path']}/{$result['file']}");

            $this->setValue("{var}/$filename");

        } else {
            $this->setValue($value[0]);
        }

        return $this;
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
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    
    public function getAPIVersion($serviceNode)
    {
        $location = $this->arrayFindValueByKey($serviceNode,'location');
        $getAPIVersion = str_replace('https://login.salesforce.com/services/Soap/c/','',$location);
        
        $apiversion = explode('/',$getAPIVersion);

        return reset($apiversion);

    }
    
    public function arrayFindValueByKey($array, $search, $keys = array())
    {
        foreach($array as $key => $value) {
            if (is_array($value)) {
                $sub = $this->arrayFindValueByKey($value, $search, array_merge($keys, array($key)));
                if (count($sub)) {
                    return $sub;
                }
            } elseif ($key === $search) {
                return $value;
            }
        }
    
        return array();
    }

    /**
     * Save cache
     *
     * @param String $value
     * @param String $identifier
     * @param int|null $websiteId
     *
     * @throws \Magento\Framework\Exception\LocalizedException
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