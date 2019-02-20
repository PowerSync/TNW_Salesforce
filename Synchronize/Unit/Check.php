<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

class Check extends Synchronize\Unit\UnitAbstract
{

    /**
     * @var array
     */
    private $process;

    public function __construct(
        $name,
        array $process,
        Synchronize\Units $units,
        Synchronize\Group $group,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($process, $dependents));
        $this->process = array_filter($process);
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Validate %1 upsert result', $this->group()->code());
    }

    /**
     *
     */
    public function process()
    {
        foreach ($this->process as $processName) {
            $process = $this->unit($processName);

            if (!$process->isComplete()) {
                throw new \RuntimeException(__('Unit "%1" not complete', $this->name()));
            }

            foreach ($process->load()->get('entities') as $entity) {
                $parentEntity = $this->findParentEntity($process->load(), $entity);
                if (empty($parentEntity)) {
                    continue;
                }

                if ($process->skipped($parentEntity)) {
                    $this->cache[$parentEntity]['skipped'] = $process->get('message/%s', $parentEntity);
                } else {
                    $this->cache[$parentEntity]['message'][] = $process->get('%s/message', $entity);
                }
            }
        }

        $checks = $skipped = [];
        $iterator = $this->cache->getIterator();
        for ($iterator->rewind(); $iterator->valid(); $iterator->next()) {
            $message = implode("\n", array_filter(iterator_to_array($this->cache[$iterator->key()]['message'])));

            $checks[] = $this->cache[$iterator->key()]['success']
                = empty($message) && empty($this->cache[$iterator->key()]['skipped']);

            $skipped[] = $this->cache->get('%s/skipped', $iterator->key());

            $this->cache[$iterator->key()]['message'] = $message;
        }

        $this->postProcess();

        if (in_array(true, $checks, true)) {
            $this->group()->messageSuccess('Total %d Magento "%s" were successfully synchronized',
                count(array_keys($checks, true, true)), $this->group()->code());
        }

        foreach (array_filter($skipped) as $skippedReason) {
            $this->group()->messageNotice($skippedReason);
        }
    }

    /**
     * @param Synchronize\Unit\LoadAbstract|Synchronize\Unit\LoadByAbstract $load
     * @param $entity
     * @return mixed
     */
    public function findParentEntity($load, $entity)
    {
        if ($load instanceof Synchronize\Unit\LoadAbstract) {
            return $entity;
        }

        return $this->findParentEntity($load->load(), $load->get('parents/%s', $entity));
    }

    /**
     * @param Synchronize\Unit\UnitInterface $unit
     * @param $entity
     * @return string
     */
    public function findSkipReason($unit, $entity)
    {
        if ($unit instanceof Synchronize\Unit\ProcessingAbstract && $unit->skipped($entity)) {
            return $unit->get('message/%s', $entity);
        }

        foreach ($unit->dependents() as $unitName) {
            if (!$this->unit($unitName)->skipped($entity)) {
                continue;
            }

            return $this->findSkipReason($this->unit($unitName), $entity);
        }

        return null;
    }

    /**
     *
     */
    protected function postProcess()
    {
        return;
    }
}