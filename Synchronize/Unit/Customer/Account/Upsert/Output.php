<?php

namespace TNW\Salesforce\Synchronize\Unit\Customer\Account\Upsert;

use TNW\Salesforce\Synchronize;

class Output extends Synchronize\Unit\Upsert\Output
{
    /**
     * @param Synchronize\Transport\Calls\Upsert\Output $output
     */
    protected function processOutput(Synchronize\Transport\Calls\Upsert\Output $output)
    {
        // TODO: Get
        $upsertEntities = [];

        // restore deDuplicate
        foreach ($this->entities() as $entity) {
            $upsertEntity = isset($upsertEntities[spl_object_hash($entity)])
                ? $upsertEntities[spl_object_hash($entity)] : $entity;

            if (empty($output[$upsertEntity]['success'])) {
                $this->group()->messageError('Upsert object "%s". Entity: %s. Message: "%s".',
                    $this->salesforceType(), $this->identification->printEntity($entity), $output[$upsertEntity]['message']);
            }

            $this->cache[$entity] = $output[$upsertEntity];
            $this->prepare($entity);
        }
    }
}
