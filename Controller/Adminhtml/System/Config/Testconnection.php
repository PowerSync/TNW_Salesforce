<?php
namespace TNW\Salesforce\Controller\Adminhtml\System\Config;

use Magento\Config\Model\Config\Backend\File;
use Magento\Backend\App\Action\Context;
/**
 * Class Testconnection
 * @package TNW\Salesforce\Controller\Adminhtml\System\Config
 */
class Testconnection extends \Magento\Backend\App\Action
{

    const UPLOAD_DIR = 'wsdl/tmp';

    /**
     * @var \Magento\Config\Model\Config\Backend\File
     */
    protected $file;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        File $file,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->file = $file;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->_uploaderFactory = $uploaderFactory;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Perform Salesforce connection test
     *
     * @return \Magento\Framework\Object
     */
    public function execute()
    {
        /**
         * @var \TNW\Salesforce\Client\Salesforce $client
         */
        $client = $this->_objectManager->get('\TNW\Salesforce\Client\Salesforce');

        /**
         * @var \TNW\Salesforce\Model\Config $config
         */
        $config = $this->_objectManager->get('\TNW\Salesforce\Model\Config');

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $file = $this->getRequest()->getFiles('file');
        if (!empty($file)) {
            $fileName = ($file && array_key_exists('name', $file)) ? $file['name'] : null;
            
            $uploadDir = $this->_getUploadDir();
            try {

                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions(['xml']);
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);

                $response = ['success' => 'true', 'message' => 'file uploaded successfully'];
                return $resultJson->setData($response);
                

            } catch (\Exception $e) {
    
                $response = ['error' => 'true', 'message' => $e->getMessage()];
                return $resultJson->setData($response);
            }

        } 

        // get website id from url
        $websiteId = $this->getRequest()->getParam('website');

        // get wsld path
        $wsdl = $this->getRequest()->getParam('wsdl');

        if(strpos($wsdl, 'fakepath') == false){
            // read website specific configuration
            $wsdl = $config->getSalesforceWsdl($websiteId);
            $username = $config->getSalesforceUsername($websiteId);
            $password = $config->getSalesforcePassword($websiteId);
            $token = $config->getSalesforceToken($websiteId);
        }else{
            // get config from current values
            $wsdl = $this->_getWsdlPath($wsdl);
            $username = $this->getRequest()->getParam('username');
            $password = $this->getRequest()->getParam('password');
            $token = $this->getRequest()->getParam('token');  
        }


        try {
            $result = $client->checkConnection($wsdl, $username, $password, $token);
        } catch (\Exception $e) {
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

    protected function _getWsdlPath($wsdl){
        $fileName = basename($wsdl);
        return $this->_mediaDirectory->getAbsolutePath(self::UPLOAD_DIR).'/'.$fileName;
    }
}
