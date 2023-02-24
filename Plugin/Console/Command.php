<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Console;

use Symfony\Component\Console\Command\Command as Subject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Salesforce\Service\Sync\Entities as SyncEntitiesService;

class Command
{
    /**
     * @var SyncEntitiesService
     */
    private $syncEntitiesService;

    /**
     * @param SyncEntitiesService $syncEntitiesService
     */
    public function __construct(
        SyncEntitiesService $syncEntitiesService
    ) {
        $this->syncEntitiesService = $syncEntitiesService;
    }

    /**
     * @param Subject $subject
     * @param int $result
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function afterRun(Subject $subject, int $result, InputInterface $input, OutputInterface $output): int
    {
        $this->syncEntitiesService->execute();

        return $result;
    }
}
