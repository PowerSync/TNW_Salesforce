<?php
namespace TNW\Salesforce\Synchronize\Unit;

interface CheckInterface extends UnitInterface
{
    /**
     * @return LoadAbstract|LoadByAbstract
     */
    public function load();

    /**
     * @return array
     */
    public function entities();
}