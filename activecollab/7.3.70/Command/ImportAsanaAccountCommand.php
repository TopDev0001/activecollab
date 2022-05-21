<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use AsanaImporterIntegration;
use Exception;
use Integrations;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Users;

/**
 * Class ImportTrelloAccountCommand.
 */
class ImportAsanaAccountCommand extends Command
{
    const MEMORY_LIMIT = '4048M';

    /**
     * Configure command.
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Import Asana Workspaces to ActiveCollab')
            ->addArgument('personal_token', InputArgument::REQUIRED, 'Asana personal token')
            ->addArgument('workspaces', InputArgument::IS_ARRAY, 'Id workspaces that you want to import (separated by a space)');
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('Setting memory limit to 4GB');
        }

        $original_memory_limit = ini_get('memory_limit');
        ini_set('memory_limit', self::MEMORY_LIMIT);
        $messages = [];

        try {
            $asana_personal_token = $input->getArgument('personal_token');

            /** @var AsanaImporterIntegration $integration */
            $integration = Integrations::findFirstByType(AsanaImporterIntegration::class);

            AngieApplication::authentication()->setAuthenticatedUser(Users::findFirstOwner());

            $integration->setAccessToken($asana_personal_token);

            $ids = $input->getArgument('workspaces');
            if (count($ids) > 0) {
                $integration->setSelectedWorkspaces($ids);
            }

            $integration->validateCredentials();
            $integration->startImport(function ($message) use ($output, &$messages) {
                $messages[] = $message;
                $output->write($message);
            });

            AngieApplication::log()->info(
                'Assana Importer - Output log.',
                [
                    'output_messages' => implode(PHP_EOL, $messages),
                ]
            );

            $this->revertMemoryLimit($output, $original_memory_limit);

            return $this->success('Done', $input, $output);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Assana Importer - Proccess failed with error.',
                [
                    'error_message' => $e->getMessage(),
                    'output_messages' => implode(PHP_EOL, $messages),
                ]
            );

            $this->revertMemoryLimit($output, $original_memory_limit);

            return $this->abortDueToException($e, $input, $output);
        }
    }

    /**
     * @param int $original_memory_limit
     */
    private function revertMemoryLimit(OutputInterface $output, $original_memory_limit)
    {
        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('Setting memory limit back to ' . $original_memory_limit);
        }

        ini_set('memory_limit', $original_memory_limit);
    }
}
