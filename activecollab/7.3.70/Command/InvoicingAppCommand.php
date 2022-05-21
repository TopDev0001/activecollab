<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command;

use ActiveCollab\Foundation\Wrappers\ConfigOptions\ConfigOptionsInterface;
use Angie\Command\Command;
use AngieApplication;
use DB;
use QuickbooksIntegration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use XeroIntegration;

class InvoicingAppCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Show information about invoicing integration in use')
            ->addOption(
                'reset',
                '',
                InputOption::VALUE_NONE, 'Reset invoicing app to built-in invoicing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ConfigOptionsInterface $config_options */
        $config_options = AngieApplication::getContainer()->get(ConfigOptionsInterface::class);

        $invoicing_app = $config_options->getValue('default_accounting_app');

        switch ($invoicing_app) {
            case 'quickbooks':
                $invoicing_app_name = 'QuickBooks Online';
                break;
            case 'xero':
                $invoicing_app_name = 'Xero';
                break;
            default:
                $invoicing_app_name = 'Built-in invoicing';
        }

        $output->writeln(sprintf('Inovicing app: <info>%s</info>.', $invoicing_app_name));

        if ($input->getOption('reset')) {
            $output->writeln('');

            $config_options->setValue('default_accounting_app', null);
            $output->writeln('<info>OK</info>: Invoicing app reset to built in invoicing.');

            DB::execute(
                'DELETE FROM `integrations` WHERE `type` IN (?)',
                [
                    QuickbooksIntegration::class,
                    XeroIntegration::class,
                ]
            );

            return $this->success(sprintf('%d invoicing integrations deleted.', DB::affectedRows()), $input, $output);
        }

        return $this->success('Done', $input, $output);
    }
}
