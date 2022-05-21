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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteDocumentsCommand extends SearchCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Delete documents for tenant');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            AngieApplication::search()->deleteDocuments();

            return $this->success('Done', $input, $output);
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
