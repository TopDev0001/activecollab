<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command\Search;

use Angie\Command\SearchCommand;
use AngieApplication;
use Exception;
use OnDemand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DistributionCommand extends SearchCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Json data for create index');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $search_engine = AngieApplication::search();

            $data = [
                'hosts' => $search_engine->getHosts(),
                'number_of_shards' => $search_engine->getNumberOfShards(),
                'number_of_replicas' => $search_engine->getNumberOfReplicas(),
                'document_type' => $search_engine->getDocumentType(),
                'document_mapping' => $search_engine->getDocumentMapping(),
            ];

            if (AngieApplication::isOnDemand()) {
                $data['indices'] = OnDemand::getSearchIndexNames();
            } else {
                $data['index'] = $search_engine->getIndexName();
            }

            $output->writeln(json_encode($data, JSON_PRETTY_PRINT));

            return 1;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
