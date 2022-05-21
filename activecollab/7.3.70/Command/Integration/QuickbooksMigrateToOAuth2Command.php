<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command\Integration;

use Angie\Command\IntegrationCommand;
use AngieApplication;
use Exception;
use Integrations;
use LogicException;
use QuickbooksIntegration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QuickbooksMigrateToOAuth2Command extends IntegrationCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Migrate OAuth1 to OAuth2 tokens for Quickbooks integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AngieApplication::log()->debug('Quickbooks migration command is called.');

        /** @var QuickbooksIntegration $quckbooks_integration */
        $quckbooks_integration = Integrations::findFirstByType(QuickbooksIntegration::class);

        try {
            $integration = $quckbooks_integration->migrateToOAuth2();

            if ($integration) {
                return $this->success(
                    '<info>Migration has been successfully done.</info>.',
                    $input,
                    $output
                );
            }

            return $this->success(
                '<warn>Skip migration. Integration is not connected.</warn>',
                $input,
                $output
            );
        } catch (LogicException $e) {
            return $this->success(
                '<warn>Skip. '. $e->getMessage() .'.</warn>',
                $input,
                $output
            );
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
