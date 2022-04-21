<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Plugin\Framework\Message;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use SoapFault;

class AddGroupToMessage
{
    private const TNW_GROUP = 'TNW';

    /**
     * Add group for soap exception.
     *
     * @param ManagerInterface $subject
     * @param Exception        $exception
     * @param null             $alternativeText
     * @param null             $group
     *
     * @return array|null
     */
    public function beforeAddExceptionMessage(
        ManagerInterface $subject,
        Exception $exception,
        $alternativeText = null,
        $group = null
    ): ?array {
        if (!($exception instanceof SoapFault) && $group === null) {
            return null;
        }

        return [$exception, $alternativeText, self::TNW_GROUP];
    }

    /**
     * @param ManagerInterface $subject
     * @param Exception        $exception
     * @param null             $alternativeText
     * @param null             $group
     *
     * @return array|null
     */
    public function beforeAddException(
        ManagerInterface $subject,
        Exception $exception,
        $alternativeText = null,
        $group = null
    ): ?array {
        if (!($exception instanceof SoapFault) && $group === null) {
            return null;
        }

        return [$exception, $alternativeText, self::TNW_GROUP];
    }
}
