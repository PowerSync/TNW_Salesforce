<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\LogFile\Sync;

use TNW\Salesforce\Controller\Adminhtml\LogFile\AbstractDownload;

/**
 * Download synchronization log file action.
 * @TODO Remove after controller renaiming
 */
class Download extends AbstractDownload
{
    /** @var string */
    protected $redirectRoutePath = '*/logfile_sync/index';
}
