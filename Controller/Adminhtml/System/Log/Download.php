<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\System\Log;

use Exception;
use InvalidArgumentException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use TNW\Salesforce\Model\Config;

class Download extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /** @var Config  */
    protected $salesforceConfig;

    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        Config $salesforceConfig
    ) {
        $this->fileFactory = $fileFactory;
        $this->salesforceConfig = $salesforceConfig;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function execute()
    {
        $baseDay = $this->salesforceConfig->logBaseDay();

        $filenamePath = sprintf('log/sforce/%d_%d_%d.log', date('Y'), $baseDay, floor((date('z') + 1) / $baseDay));

        return $this->fileFactory->create('sforce.log', [
            'type'  => 'filename',
            'value' => $filenamePath
        ], DirectoryList::VAR_DIR);
    }
}
