<?php

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
