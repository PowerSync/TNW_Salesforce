<?php
namespace TNW\Salesforce\Controller\Adminhtml\System\Config;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use TNW\Salesforce\Client\Salesforce;
use TNW\Salesforce\Model\Config;

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

    /** @var Filesystem\Directory\WriteInterface  */
    protected $_mediaDirectory;

    public function __construct(
        Context $context,
        File $file,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->file = $file;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_uploaderFactory = $uploaderFactory;
        $this->resultJsonFactory = $resultJsonFactory;
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

        /**
         * @var Config $config
         */
        $config = $this->_objectManager->get('\TNW\Salesforce\Model\Config');

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $file = $this->getRequest()->getFiles('file');
        if (!empty($file)) {

            $uploadDir = $this->_getUploadDir();
            try {

                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions(['xml', 'wsdl']);
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);

                $response = ['success' => 'true', 'message' => 'file uploaded successfully'];
                return $resultJson->setData($response);
            } catch (Exception $e) {
                $response = ['error' => 'true', 'message' => $e->getMessage()];
                return $resultJson->setData($response);
            }
        }

        // get website id from url
        $websiteId = $this->getRequest()->getParam('website');

        // get wsld path
        $wsdl = $this->getRequest()->getParam('wsdl');

        if (strpos($wsdl, 'fakepath') === false) {
            // read website specific configuration
            $wsdl = $config->getSalesforceWsdl($websiteId);
            $location = $config->getSFDCLocationEndpoint($websiteId);
            $username = $config->getSalesforceUsername($websiteId);
            $password = $config->getSalesforcePassword($websiteId);
            $token = $config->getSalesforceToken($websiteId);
        } else {
            // get config from current values
            $wsdl = $this->_getWsdlPath($wsdl);
            $location = $this->getRequest()->getParam('endpoint');
            $username = $this->getRequest()->getParam('username');
            $password = $this->getRequest()->getParam('password');
            $token = $this->getRequest()->getParam('token');
        }

        try {
            $result = $client->checkConnection($wsdl, $location, $username, $password, $token);
        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        $this->getResponse()->setBody($result);
    }

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath(self::UPLOAD_DIR);
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
