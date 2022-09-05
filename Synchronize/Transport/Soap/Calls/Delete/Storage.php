<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Transport\Soap\Calls\Delete;

use Tnw\SoapClient\Result\DeleteResult;
use function spl_object_hash;

/**
 * Soap Upsert Storage
 */
class Storage
{
    /**
     * @var array
     */
    private $results = [];

    /**
     * Save Result
     *
     * @param object $entity
     * @param DeleteResult $result
     */
    public function saveResult($entity, $result)
    {
        // hack, actually different objects could have the same hash
        $classType = get_class($entity);
        $this->results[spl_object_hash($entity) . $classType][] = $result;
    }

    /**
     * Search Result
     *
     * @param object $entity
     * @return DeleteResult
     */
    public function searchResult($entity)
    {
        // hack, actually different objects could have the same hash
        $classType = get_class($entity);
        $hash = spl_object_hash($entity) . $classType;
        if (empty($this->results[$hash])) {
            return null;
        }

        $result = null;
        foreach ($this->results[$hash] as $item) {
            if (empty($result) || !$item->isSuccess()) {
                $result = $item;
            }
        }

        return $result;
    }
}
