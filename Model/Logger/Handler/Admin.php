<?php
namespace TNW\Salesforce\Model\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;

class Admin extends AbstractProcessingHandler
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Message constructor.
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->messageManager = $messageManager;
        $this->appState = $appState;
        $this->request = $request;
        parent::__construct(\Monolog\Logger::INFO);
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
        if (strcasecmp($this->request->getActionName(), 'inlineEdit') === 0) {
            return;
        }

        switch ($record['level']) {
            case \Monolog\Logger::ERROR:
                $this->messageManager->addErrorMessage($record['message'], 'backend');
                break;

            case \Monolog\Logger::WARNING:
                $this->messageManager->addWarningMessage($record['message'], 'backend');
                break;

            case \Monolog\Logger::INFO:
                $this->messageManager->addSuccessMessage($record['message'], 'backend');
                break;

            case \Monolog\Logger::NOTICE:
                $this->messageManager->addNoticeMessage($record['message'], 'backend');
                break;
        }
    }
}