<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use Exception;
use ProjectElementsSearchBuilderInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Angie\Command
 */
final class RebuildTasksSearchIndexCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Rebuild tasks search index');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $builders = AngieApplication::search()->getBuilders();

            foreach ($builders as $builder) {
                if ($builder instanceof ProjectElementsSearchBuilderInterface
                    && $builder->getProjectElements() === 'tasks'
                ) {
                    $output->writeln("<info>OK:</info> {$builder->getName()}");

                    /** @var ProgressBar|null $progress */
                    $progress = null;
                    $count_until = 0;

                    $builder->build(
                        function ($iteration, $total_records) use (&$progress, &$count_until, $output) {
                            if ($total_records > 1000) {
                                if (empty($progress)) {
                                    $count_until = $total_records;

                                    $progress = new ProgressBar($output, $count_until);
                                    $progress->start();
                                }

                                $progress->setProgress($iteration);
                            }
                        },
                        50
                    );

                    if ($progress) {
                        $progress->setProgress($count_until);
                        $output->writeln("\n");
                    }
                }
            }

            return $this->success('Done in ' . AngieApplication::getExecutionTime() . 's', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
