<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Plugin\Synchronize\Transport\Soap\Calls\Upsert\Storage;

use Magento\Catalog\Api\Data\ProductInterface;
use ReflectionException;
use ReflectionProperty;
use TNW\Salesforce\Synchronize\Transport\Soap\Calls\Upsert\Storage;
use TNW\Salesforce\Synchronize\Unit\CurrentUnit;
use TNW\Salesforce\Synchronize\Unit\Upsert\Input;
use Tnw\SoapClient\Result\SaveResult;

/**
 *  Find archived product error
 */
class FormatResponseForArchivedProductsPlugin
{
    private const ERROR_CODE = 'DUPLICATE_VALUE';
    private const LINK = 'https://technweb.atlassian.net/wiki/spaces/IWS/pages/3240394753/Synchronization+of+Magento+product+with+Archived+product+in+Salesforce';

    /** @var CurrentUnit */
    private $currentUnit;

    /**
     * @param CurrentUnit $currentUnit
     */
    public function __construct(CurrentUnit $currentUnit)
    {
        $this->currentUnit = $currentUnit;
    }

    /**
     * Check and prepare message
     *
     * @param Storage    $subject
     * @param object     $entity
     * @param SaveResult $result
     *
     * @return array
     * @throws ReflectionException
     */
    public function beforeSaveResult(Storage $subject, object $entity, SaveResult $result)
    {
        $unit = $this->currentUnit->getUnit();
        if ($entity instanceof ProductInterface &&
            $unit instanceof Input &&
            $unit->unit('lookup') &&
            !$result->isSuccess()
        ) {
            $lookup = $unit->unit('lookup');
            $lookupObject = $lookup->get('%s/record', $entity);
            if (!$lookupObject) {
                foreach ($result->getErrors() as $error) {
                    $wasFound = $error->getStatusCode() === self::ERROR_CODE;
                    if ($wasFound) {
                        $messageProperty = new ReflectionProperty($error, 'message');
                        $messageProperty->setAccessible(true);
                        $format = 'Item cannot be synced! Reason: "Product is archived!". For more information click %s';
                        $link = sprintf(
                            '<a href="%s">here</a>',
                            self::LINK
                        );

                        $message = sprintf($format, $link);
                        $messageProperty->setValue($error, __($message)->render());
                    }
                }
            }
        }

        return [$entity, $result];
    }
}
