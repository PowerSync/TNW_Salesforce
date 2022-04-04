<?php
declare(strict_types=1);

namespace TNW\Salesforce\Plugin\Synchronize\Transport\Soap\Calls\Upsert\Storage;

use Magento\Catalog\Api\Data\ProductInterface;
use TNW\Salesforce\Synchronize\Unit\CurrentUnit;
use TNW\Salesforce\Synchronize\Unit\Upsert\Input;
use Tnw\SoapClient\Result\UpsertResult;

/**
 *  Find archived product error
 */
class FormatResponseForArchivedProductsPlugin
{
    private const ERROR_CODE = 'DUPLICATE_VALUE';

    /** @var CurrentUnit */
    private $currentUnit;

    public function __construct(
        CurrentUnit $currentUnit
    )
    {
        $this->currentUnit = $currentUnit;
    }

    /**
     * Check and prepare message
     *
     * @param              $subject
     * @param              $entity
     * @param UpsertResult $result
     *
     * @return array
     * @throws \ReflectionException
     */
    public function beforeSaveResult($subject, $entity, UpsertResult $result)
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
                        $messageProperty = new \ReflectionProperty($error, 'message');
                        $messageProperty->setAccessible(true);
                        $messageProperty->setValue($error, __('Product is archived!')->render());
                    }
                }
            }
        }

        return [$entity, $result];
    }
}
