<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command\Search;

use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\CreateIndex;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\DeleteIndex;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus;
use ActiveCollab\ActiveCollabJobs\Jobs\ElasticSearch\IndexStatus\IndexStatusInterface;
use Angie\Command\SearchCommand;
use Angie\Search\SearchEngineInterface;
use Angie\Search\SearchIndexResolver\MultiTenantIndexResolver;
use AngieApplication;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class IndexCommand extends SearchCommand
{
    const ACTION_STATUS = 'status';
    const ACTION_CREATE = 'create';
    const ACTION_DELETE = 'delete';

    const ACTIONS = [
        self::ACTION_STATUS,
        self::ACTION_CREATE,
        self::ACTION_DELETE,
    ];

    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Show search index status')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'What to do with the index (options: ' . implode(', ', self::ACTIONS) . ').',
                self::ACTION_STATUS
            )
            ->addOption(
                'index-name',
                '',
                InputOption::VALUE_REQUIRED,
                'Override default index name provided by search integration'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force creation of the index if index already exists.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $action = $this->getAction($input);

            $search_engine = AngieApplication::search();

            $index_name = $this->getIndexName($search_engine, $input, $output);
            $index_job_arguments = [
                'hosts' => $search_engine->getHosts(),
                'index' => $index_name,
                'instance_id' => AngieApplication::getAccountId(),
            ];

            /** @var IndexStatusInterface $index_status */
            $index_status = AngieApplication::jobs()->execute(
                new IndexStatus($index_job_arguments),
                false
            );

            switch ($action) {
                case self::ACTION_CREATE:
                    if ($index_status->indexExists()) {
                        if ($input->getOption('force')) {
                            $output->writeln(
                                sprintf(
                                    '<comment>Notice</comment>: Index <comment>%s</comment> already exist. Dropping, and creating an empty one...',
                                    $index_name
                                )
                            );

                            $this->createIndex($search_engine, $index_name, $index_job_arguments, $output);
                        } else {
                            $output->writeln(
                                sprintf(
                                    '<comment>Notice</comment>: Index <comment>%s</comment> already exist. Skipping...',
                                    $index_name
                                )
                            );
                        }
                    } else {
                        $this->createIndex($search_engine, $index_name, $index_job_arguments, $output);
                    }

                    break;
                case self::ACTION_DELETE:
                    if ($index_status->indexExists()) {
                        AngieApplication::jobs()->execute(
                            new DeleteIndex($index_job_arguments),
                            false
                        );

                        $output->writeln(
                            sprintf(
                                '<info>OK</info>: Index <comment>%s</comment> has been dropped...',
                                $index_name
                            )
                        );

                        /** @var IndexStatusInterface $index_status */
                        $index_status = AngieApplication::jobs()->execute(
                            new IndexStatus($index_job_arguments),
                            false
                        );
                        $this->showIndexStatus($index_name, $search_engine, $index_status, $output);
                    } else {
                        $output->writeln(
                            sprintf(
                                '<comment>Notice</comment>: Index <comment>%s</comment> does not exist...',
                                $index_name
                            )
                        );
                    }
                    break;
                default:
                    $this->showIndexStatus($index_name, $search_engine, $index_status, $output);
            }

            return 0;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    private function createIndex(
        SearchEngineInterface $search_engine,
        $index_name,
        array $index_job_arguments,
        OutputInterface $output
    )
    {
        AngieApplication::jobs()->execute(
            new CreateIndex(
                array_merge(
                    $index_job_arguments,
                    [
                        'number_of_shards' => $search_engine->getNumberOfShards(),
                        'number_of_replicas' => $search_engine->getNumberOfReplicas(),
                        'document_type' => $search_engine->getDocumentType(),
                        'document_mapping' => $search_engine->getDocumentMapping(),
                    ]
                )
            ),
            false
        );

        $output->writeln(
            sprintf(
                '<info>OK</info>: Index <comment>%s</comment> has been created...',
                $index_name
            )
        );

        /** @var IndexStatusInterface $index_status */
        $index_status = AngieApplication::jobs()->execute(
            new IndexStatus($index_job_arguments),
            false
        );
        $this->showIndexStatus($index_name, $search_engine, $index_status, $output);
    }

    private function getAction(InputInterface $input)
    {
        $action = $input->getArgument('action');

        if (!in_array($action, self::ACTIONS)) {
            throw new InvalidArgumentException('Invalid action argument value.');
        }

        return $action;
    }

    private function showIndexStatus(
        string $index_name,
        SearchEngineInterface $search_engine,
        IndexStatusInterface $index_status,
        OutputInterface $output
    )
    {
        $metadata = [
            'adapter' => $search_engine->getAdapterName(),
        ];

        $metadata['mode'] = AngieApplication::searchIndexResolver() instanceof MultiTenantIndexResolver
            ? 'multi-tenant'
            : 'single-tenant';

        $metadata['index'] = "<comment>{$index_name}</comment>";

        if ($index_status->indexExists()) {
            $metadata['index'] .= ' (<info>found</info>)';

            $metadata['created_at'] = date('Y-m-d H:i:s', $index_status->getCreationTimestamp());
            $metadata['number_of_shards'] = $index_status->getNumberOfShards();
            $metadata['number_of_replicas'] = $index_status->getNumberOfReplicas();
            $metadata['document_count'] = $index_status->getDocumentCount();
        } else {
            $metadata['index'] .= ' (<warn>not found</warn>)';
        }

        $this->renderMetadata($metadata, $output);
    }

    private function getIndexName(SearchEngineInterface $search_engine, InputInterface $input, OutputInterface $output): string
    {
        $default_index_name = $search_engine->getIndexName();
        $index_name = trim($input->getOption('index-name'));

        if ($index_name) {
            $output->writeln(
                sprintf(
                    '<warn>Warning!</warn> Default index name (<comment>%s</comment>) has been overriden with user provided <comment>%s</comment> name!',
                    $default_index_name,
                    $index_name
                )
            );

            return $index_name;
        }

        return $default_index_name;
    }

    private function renderMetadata(array $metadata, OutputInterface $output)
    {
        $table = new Table($output);

        $table->setHeaders(['Property', 'Value']);

        foreach ($metadata as $k => $v) {
            $table->addRow([$k, $v]);
        }

        $table->render();
    }
}
