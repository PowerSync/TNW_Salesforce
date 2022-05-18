<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Config\Source\Salesforce;

use Magento\Framework\DataObject;
use Magento\Framework\Option\ArrayInterface;
use Throwable;
use TNW\Salesforce\Api\Service\Admin\AddUniqueExceptionMessageInterface;
use TNW\Salesforce\Synchronize\Transport\Calls\Query\Output;
use TNW\Salesforce\Synchronize\Transport\Soap\Entity\Repository\Base as SalesforceEntityRepository;

/**
 * Class Base
 * @package TNW\Salesforce\Model\Config\Source\Customer
 */
class Base extends DataObject implements ArrayInterface
{
    /** @var SalesforceEntityRepository */
    protected $salesforceEntityRepository;

    /** @var AddUniqueExceptionMessageInterface */
    protected $addUniqueExceptionMessage;

    /**
     * @param AddUniqueExceptionMessageInterface $addUniqueExceptionMessage
     * @param SalesforceEntityRepository         $salesforceEntityRepository
     * @param array                              $data
     */
    public function __construct(
        AddUniqueExceptionMessageInterface $addUniqueExceptionMessage,
        SalesforceEntityRepository $salesforceEntityRepository,
        array $data = []
    ) {
        parent::__construct($data);

        $this->salesforceEntityRepository = $salesforceEntityRepository;
        $this->addUniqueExceptionMessage = $addUniqueExceptionMessage;
    }

    /**
     * @return Output|array
     */
    public function getObjects()
    {
        return $this->salesforceEntityRepository->search();
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $entities= [];

        try {
            $entities = $this->getObjects();
            $options[''] = ' ';
            foreach($entities as $data) {
                $options[$data['Id']] = $data['Name'];
            }
        } catch (Throwable $e) {
            $this->addUniqueExceptionMessage->execute($e);
        }

        return $options;
    }
}
