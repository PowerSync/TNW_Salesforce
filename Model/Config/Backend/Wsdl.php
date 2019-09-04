<?php
namespace TNW\Salesforce\Model\Config\Backend;

use Magento\MediaStorage\Model\File\Uploader;
use Magento\Framework\Simplexml\Config;

class Wsdl extends \Magento\Config\Model\Config\Backend\File
{

    /** @var \Magento\Framework\Xml\Parser */
    protected $_xmlParser;

    /** @var \Magento\Config\Model\ResourceModel\Config */
    protected $_resourceConfig;
 

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
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $uploaderFactory,
            $requestData, $filesystem, $resource, $resourceCollection, $data);

        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);

        $this->_xmlParser = $xmlParser;

        $this->_resourceConfig = $resourceConfig;

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

                $parsedXMLData = $this->_xmlParser->load($loadedxmlfile)->xmlToArray();

                if(isset($parsedXMLData['definitions']['_value']['service'])){
                    $serviceNode = $parsedXMLData['definitions']['_value']['service'];
                    $apiVersion = $this->getAPIVersion($serviceNode);

                    if($apiVersion){
                        $this->_resourceConfig->saveConfig(
                            'tnwsforce_general/synchronization/use_bulk_api_version',
                            $apiVersion,
                            'default',
                            0
                        );
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
        $location = $this->array_find_deep($serviceNode,'location');
        $getAPIVersion = str_replace('https://login.salesforce.com/services/Soap/c/','',$location);
        
        $apiversion = explode('/',$getAPIVersion);

        return reset($apiversion);

    }
    
    public function array_find_deep($array, $search, $keys = array())
    {
        foreach($array as $key => $value) {
            if (is_array($value)) {
                $sub = $this->array_find_deep($value, $search, array_merge($keys, array($key)));
                if (count($sub)) {
                    return $sub;
                }
            } elseif ($key === $search) {
                return $value;
            }
        }
    
        return array();
    }
    

    
}