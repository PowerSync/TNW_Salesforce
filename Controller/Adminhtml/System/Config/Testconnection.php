<?php

namespace TNW\Salesforce\Controller\Adminhtml\System\Config;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\ScopeInterface;
use TNW\Salesforce\Client\Salesforce;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\Backend\Wsdl;

/**
 * Class Testconnection
 * @package TNW\Salesforce\Controller\Adminhtml\System\Config
 */
class Testconnection extends Action
{
    const UPLOAD_DIR = 'wsdl/tmp';

    /**
     * @var File
     */
    protected $file;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /** @var Filesystem\Directory\WriteInterface */
    protected $_mediaDirectory;

    /** @var \Magento\Config\Model\ResourceModel\Config */
    protected $resourceConfig;

    /** @var UploaderFactory */
    protected $_uploaderFactory;

    /** @var Wsdl */
    protected $wsdl;

    /**
     * Testconnection constructor.
     * @param Context $context
     * @param File $file
     * @param Filesystem $filesystem
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param UploaderFactory $uploaderFactory
     * @param JsonFactory $resultJsonFactory
     * @throws FileSystemException
     */
    public function __construct(
        Context $context,
        File $file,
        Filesystem $filesystem,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        UploaderFactory $uploaderFactory,
        Wsdl $wsdl,
        JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->file = $file;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_uploaderFactory = $uploaderFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->wsdl = $wsdl;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @param $file
     * @return bool
     */
    public function saveFile($file)
    {
        if (!empty($file)) {
            $uploadDir = $this->wsdl->getUploadDir();

            /** @var Uploader $uploader */
            $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
            $uploader->setAllowedExtensions(['xml']);
            $uploader->setAllowRenameFiles(false);
            $uploader->addValidateCallback('size', $this, 'validateMaxSize');
            $result = $uploader->save($uploadDir);


            return $result;
        }

        return false;
    }

    /**
     * @param $file
     * @return bool
     */
    public function updateWsql($file)
    {
        if (!empty($file)) {
            $uploadDir = $this->wsdl->getUploadDir();

            /** @var Uploader $uploader */
            $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
            $uploader->setAllowedExtensions(['xml']);
            $uploader->setAllowRenameFiles(false);
            $uploader->addValidateCallback('size', $this, 'validateMaxSize');
            $result = $uploader->save($uploadDir);

            $filename = $this->wsdl->getFilePath($result);
            return $filename;
        }

        return false;
    }

    /**
     * @return Json|void
     */
    public function processFile()
    {

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $file = $this->getRequest()->getFiles('file');

        try {
            if ($this->saveFile($file)) {
                $response = ['success' => 'true', 'message' => 'file uploaded successfully'];
                return $resultJson->setData($response);
            }
        } catch (Exception $e) {
            $response = ['error' => 'true', 'message' => $e->getMessage()];
            return $resultJson->setData($response);
        }
        return;
    }

    /**
     * Perform Salesforce connection test
     */
    public function execute()
    {
        /**
         * @var Salesforce $client
         */
        $client = $this->_objectManager->get('\TNW\Salesforce\Client\Salesforce');

        if (!empty($result = $this->processFile())) {
            return $result;
        }

        [$wsdl, $username, $password, $token] = $this->getCredentialsByRequest();

        try {
            $result = $client->checkConnection($wsdl, $username, $password, $token);

            $wsdl = $this->updateWsql($wsdl);
            $this->saveCredentials($wsdl, $username, $password, $token);
        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        $this->getResponse()->setBody($result);
    }

    /**
     * @return array
     * @throws FileSystemException
     */
    public function getCredentialsByRequest()
    {
        /**
         * @var Config $config
         */
        $config = $this->_objectManager->get('\TNW\Salesforce\Model\Config');
        // get website id from url
        $websiteId = $this->getRequest()->getParam('website');

        $username = $this->getRequest()->getParam('username', $config->getSalesforceUsername($websiteId));
        $password = $this->getRequest()->getParam('password', $config->getSalesforcePassword($websiteId));
        $token = $this->getRequest()->getParam('token', $config->getSalesforceToken($websiteId));

        // get wsld path
        $wsdl = $this->getRequest()->getParam('wsdl');

        if (strpos($wsdl, 'fakepath') === false) {
            // read website specific configuration
            $wsdl = $config->getSalesforceWsdl($websiteId);
        } else {
            // get config from current values
            $wsdl = $this->_getWsdlPath($wsdl);
        }

        return [$wsdl, $username, $password, $token];
    }

    /**
     *
     */
    public function saveCredentials($wsdl, $username, $password, $token)
    {

        $websiteId = $this->getRequest()->getParam('website');

        $this->resourceConfig->saveConfig('tnwsforce_general/salesforce/wsdl', $wsdl, ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $this->resourceConfig->saveConfig('tnwsforce_general/salesforce/username', $username, ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $this->resourceConfig->saveConfig('tnwsforce_general/salesforce/password', $password, ScopeInterface::SCOPE_WEBSITE, $websiteId);
        $this->resourceConfig->saveConfig('tnwsforce_general/salesforce/token', $token, ScopeInterface::SCOPE_WEBSITE, $websiteId);

    }

    /**
     * @param $wsdl
     * @return string
     */
    protected function _getWsdlPath($wsdl)
    {
        $wsdl = basename($wsdl);

        $fileName = substr(strrchr($wsdl, '\\'), 1);
        if (empty($fileName)) {
            $fileName = $wsdl;
        }

        return $this->_mediaDirectory->getAbsolutePath(self::UPLOAD_DIR) . '/' . $fileName;
    }
}
