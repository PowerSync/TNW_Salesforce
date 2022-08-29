<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin;

use Magento\Framework\File\Uploader;

class SaveImageInformation
{
    /**
     * @param Uploader $subject
     * @param array $result
     * @return array
     */
    public function afterSave(Uploader $subject, array $result): array
    {
        return $result;
    }
}
