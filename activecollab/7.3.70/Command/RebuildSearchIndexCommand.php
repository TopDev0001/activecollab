<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\DeleteDocuments;
use ActiveCollab\ActiveCollabJobs\Jobs\Search\CreateIndex;
use ActiveCollab\ActiveCollabJobs\Jobs\Search\CreateMapping;
use ActiveCollab\ActiveCollabJobs\Jobs\Search\DeleteDocument;
use ActiveCollab\ActiveCollabJobs\Jobs\Search\DeleteIndex;
use ActiveCollab\ActiveCollabJobs\Jobs\Search\IndexDocument;
use ActiveCollab\ActiveCollabJobs\Jobs\Search\Query;
use Angie\Command\Command;
use Angie\Search\Adapter\Queued;
use Angie\Search\SearchBuilder\SearchBuilderInterface;
use AngieApplication;
use DB;
use Exception;
use Integrations;
use ProjectElementsSearchBuilderInterface;
use SearchIntegration;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Angie\Command
 */
final class RebuildSearchIndexCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Rebuild search index')
            ->addOption('skip-tasks', '', InputOption::VALUE_NONE, 'Skip tasks')
            ->addOption('skip-sleep', '', InputOption::VALUE_NONE, 'Skip 1s sleep');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isIndexAvailable()) {
            return $this->abort(
                'Index does not exists. You need to create index first.',
                1,
                $input,
                $output
            );
        }

        $output->writeln('<info>OK:</info> Rebuilding search');

        try {
            DB::execute(
                'DELETE FROM `jobs_queue` WHERE type IN (?)',
                [
                    Query::class,
                    CreateIndex::class,
                    DeleteIndex::class,
                    CreateMapping::class,
                    IndexDocument::class,
                    DeleteDocument::class,
                    DeleteDocuments::class,
                ]
            ); // Remove all existing search tasks from jobs queue

            AngieApplication::search()->reset();

            if (!$input->getOption('skip-sleep')) {
                $output->writeln('<info>OK:</info> Sleeping for a second...');
                sleep(1);
            }

            $skip_tasks = $input->getOption('skip-tasks');

            /** @var SearchBuilderInterface[] $builders */
            $builders = AngieApplication::search()->getBuilders();

            foreach ($builders as $builder) {
                if ($this->shouldSkip($skip_tasks, $builder)) {
                    $output->writeln('<warn>Notice</warn>: Skipping tasks builder...');
                    continue;
                }

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

            if (!AngieApplication::isOnDemand()) {
                /** @var SearchIntegration $integration */
                $integration = Integrations::findFirstByType(SearchIntegration::class);

                $integration->setIndexRebuildedFlag(true);
                $integration->save();
            }

            return $this->success('Done in ' . AngieApplication::getExecutionTime() . 's', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    /**
     * @param       $skip_tasks
     * @return bool
     */
    private function shouldSkip($skip_tasks, SearchBuilderInterface $builder)
    {
        return $skip_tasks
            && $builder instanceof ProjectElementsSearchBuilderInterface
            && $builder->getProjectElements() === 'tasks';
    }

    /**
     * Check if index exists.
     *
     * @return bool
     */
    private function isIndexAvailable()
    {
        return AngieApplication::search()->getAdapterName() === Queued::class
            && !AngieApplication::search()->doesIndexExists();
    }
}
