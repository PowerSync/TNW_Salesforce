<?php
namespace TNW\Salesforce\Controller\Adminhtml\System\Log;

use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        return $this->fileFactory->create('sforce.log', [
            'type'  => 'filename',
            'value' => 'log/sforce.log'
        ], DirectoryList::VAR_DIR);
    }
}