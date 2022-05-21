<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use BasecampImporterIntegration;
use Exception;
use Integrations;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Users;

/**
 * @package ActiveCollab\Command
 */
class ImportBasecampAccountCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Import Basecamp Projects to ActiveCollab')
            ->addArgument('account', InputArgument::REQUIRED, 'Basecamp account ID')
            ->addArgument('username', InputArgument::REQUIRED, 'Basecamp account username')
            ->addArgument('password', InputArgument::REQUIRED, 'Basecamp account password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messages = [];

        try {
            // get command arguments
            $basecamp_application_id = $input->getArgument('account');
            $basecamp_username = $input->getArgument('username');
            $basecamp_password = $input->getArgument('password');

            // load basecamp importer integration
            $integration = Integrations::findFirstByType('BasecampImporterIntegration');
            if (!($integration instanceof BasecampImporterIntegration)) {
                throw new Exception('Basecamp importer integration does not exists');
            }

            // log first owner as logged user
            AngieApplication::authentication()->setAuthenticatedUser(Users::findFirstOwner());

            // set basecamp credentials
            $integration->setCredentials($basecamp_username, $basecamp_password, $basecamp_application_id);

            // validate credentials
            $integration->validateCredentials();

            // start the import process
            $integration->startImport(
                function ($message) use ($output, &$messages) {
                    $messages[] = $message;
                    $output->write($message);
                }
            );

            AngieApplication::log()->info(
                'Basecamp Importer - Output log.',
                [
                    'output_messages' => implode(PHP_EOL, $messages),
                ]
            );

            return $this->success('Done', $input, $output);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Basecamp Importer - Proccess failed with error.',
                [
                    'error_message' => $e->getMessage(),
                    'output_messages' => implode(PHP_EOL, $messages),
                ]
            );

            return $this->abortDueToException($e, $input, $output);
        }
    }
}
