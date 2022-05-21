<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command\Search;

use Angie\Command\SearchCommand;
use Angie\Search\SearchResult\SearchResultInterface;
use AngieApplication;
use Exception;
use InvalidArgumentException;
use IProjectElement;
use Projects;
use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use User;
use Users;

final class QueryCommand extends SearchCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Run a query on the live search server.')
            ->addArgument(
                'query_string',
                InputArgument::REQUIRED,
                'What to search for.'
            )
            ->addArgument(
                'query_as_user_id',
                InputArgument::OPTIONAL,
                'Enter ID or email of user as whom you want to run this query as.'
            )
            ->addOption(
                'type',
                '',
                InputOption::VALUE_REQUIRED,
                'Search for a type.'
            )
            ->addOption(
                'project_id',
                '',
                InputOption::VALUE_REQUIRED,
                'Search in a project.'
            )
            ->addOption(
                'timeframe',
                '',
                InputOption::VALUE_REQUIRED,
                'Search in timeframe.'
            )
            ->addOption(
                'user_id',
                '',
                InputOption::VALUE_REQUIRED,
                'Search by user.'
            )
            ->addOption(
                'page',
                '',
                InputOption::VALUE_REQUIRED,
                'Search result page.',
                1
            )
            ->addOption(
                'documents_per_page',
                '',
                InputOption::VALUE_REQUIRED,
                'Number of documents per page.',
                25
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $search_engine = AngieApplication::search();

        try {
            $query_string = (string) $input->getArgument('query_string');

            if (empty($query_string)) {
                throw new InvalidArgumentException('Query argument is required.');
            }

            $user_id = $input->getArgument('query_as_user_id');

            if (is_int($user_id)) {
                $user = Users::findById($user_id);
            } elseif (is_string($user_id)) {
                $user = Users::findByEmail($user_id);
            } else {
                $user = Users::findFirstOwner();
            }

            if (!$user instanceof User) {
                throw new RuntimeException('User not found.');
            }

            $output->writeln(
                sprintf(
                    '<info>OK</info>: Searching for <comment>%s</comment> as <comment>%s</comment>...',
                    $query_string,
                    $user->getEmail()
                )
            );

            $search_result = $search_engine->query(
                $query_string,
                $user,
                null,
                (int) $input->getOption('page'),
                (int) $input->getOption('documents_per_page')
            );

            if ($search_result->getTotal() > 0) {
                $this->renderSearchResult($search_result, $output);
            } else {
                $output->writeln('<info>OK</info>: Search returned no results.');
            }

            return 0;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }

    private function renderSearchResult(SearchResultInterface $search_result, OutputInterface $output)
    {
        $table = new Table($output);

        $table->setHeaders(['#', 'Type', 'ID', 'Project ID', 'Name', 'Score', 'Name Highlights', 'Body Highlights']);

        $counter = 0;

        foreach ($search_result->getHits() as $search_result_hit) {
            $number_of_name_highlights = count($search_result_hit->getNameHighlights());
            $number_of_body_highlights = count($search_result_hit->getBodyHighlights());

            $hit = $search_result_hit->getHit();

            $table->addRow(
                [
                    ++$counter . '.',
                    get_class($hit),
                    $hit->getId(),
                    $hit instanceof IProjectElement ? $this->getProjectName($hit) . ' (#' .  $hit->getProjectId(). ')' : '',
                    $hit->getName(),
                    $search_result_hit->getScore(),
                    $number_of_name_highlights ? $number_of_name_highlights : '',
                    $number_of_body_highlights ? $number_of_body_highlights : '',
                ]
            );
        }

        $table->render();
        $output->writeln(
            sprintf(
                'Search returned <comment>%d documents</comment> in <comment>%s</comment>. Showing <comment>page %d</comment>, with <comment>%d documents</comment> per page.',
                $search_result->getTotal(),
                $this->getFormattedExecutionTime($search_result->getExecTime()),
                $search_result->getPage(),
                $search_result->getDocumentsPerPage()
            )
        );
    }

    private function getProjectName(IProjectElement $project_element)
    {
        $project_id = $project_element->getProjectId();

        return Projects::getIdNameMapByIds([$project_id])[$project_id];
    }

    private function getFormattedExecutionTime($execution_time_in_ms)
    {
        return $execution_time_in_ms > 1000
            ? number_format($execution_time_in_ms / 1000, 3, '.', '') . ' seconds'
            : $execution_time_in_ms . ' miliseconds';
    }
}
