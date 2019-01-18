<?php
namespace TNW\Salesforce\Synchronize;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Synchronize\Queue\GeneratorInterface;

class Queue
{
    /**
     * @var string
     */
    private $magentoType;

    /**
     * @var string
     */
    private $salesforceType;

    /**
     * @var GeneratorInterface[]
     */
    private $loaders;

    /**
     * @var Queue[]
     */
    private $children;

    /**
     * Queue constructor.
     * @param string $magentoType
     * @param string $salesforceType
     * @param array $loaders
     * @param Queue[] $children
     */
    public function __construct(
        $magentoType,
        $salesforceType,
        array $loaders,
        array $children = []
    ) {
        $this->magentoType = $magentoType;
        $this->salesforceType = $salesforceType;
        $this->loaders = $loaders;
        $this->children = $children;
    }

    /**
     * @return string
     */
    public function magentoType()
    {
        return $this->magentoType;
    }

    /**
     * @param \TNW\Salesforce\Model\Queue $queue
     * @throws LocalizedException
     */
    private function generate($queue)
    {
        array_map([$this, 'children'], array_map([$this, 'save'], $this->prepare($queue)));
    }

    /**
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws LocalizedException
     */
    private function prepare($queue)
    {
        return $this->generator($queue)->process($queue->getMagentoId(), [$this, 'create']);
    }

    /**
     * @param $queue
     * @throws LocalizedException
     */
    private function children($queue)
    {
        foreach ($this->children as $child) {
            $child->generate($queue);
        }
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @return \TNW\Salesforce\Model\Queue
     * @return void
     */
    public function create($loadBy, $entityId)
    {
        $this->magentoType;
        $this->salesforceType;
        $loadBy;
        $entityId;
        //TODO: Implement
    }

    /**
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return \TNW\Salesforce\Model\Queue
     */
    private function save($queue)
    {
        //TODO: Implement
        return $queue;
    }

    /**
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return GeneratorInterface
     * @throws LocalizedException
     */
    public function generator($queue)
    {
        $loaderType = $queue->getParent() instanceof \TNW\Salesforce\Model\Queue
            ? $queue->getParent()->getMagentoType()
            : $queue->getMagentoType();

        if (empty($this->loaders[$loaderType])) {
            throw new LocalizedException(__('Undefined type: %1', $loaderType));
        }

        return $this->loaders[$loaderType]->queues($queue);
    }

    /**
     * @param string $loadBy
     * @param int $entityId
     * @throws LocalizedException
     */
    public function process($loadBy, $entityId)
    {
        $this->generate($this->create($loadBy, $entityId));
    }
}
