<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEnvironmentCommand extends Command
{
    protected function configure()
    {
        parent::configure();

        $this->setDescription('Check compatibility of command line environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (AngieApplication::isOnDemand() && !AngieApplication::isInDevelopment()) {
            return $this->abort(
                'This command is available only for self-hosted instances',
                1,
                $input,
                $output
            );
        }

        $current_version = AngieApplication::getVersion();

        $output->writeln(
            "<info>OK</info>: Current ActiveCollab version is <comment>{$current_version}</comment>."
        );

        AngieApplication::autoUpgrade()->includeLatestUpgradeClasses();

        $environment_is_good = AngieApplication::autoUpgrade()->checkEnvironment(
            function ($message) use (&$output) {
                $output->writeln("<info>OK:</info> {$message}.");
            },
            function ($message) use (&$output) {
                $output->writeln("<error>Error:</error> {$message}.");
            }
        );

        if ($environment_is_good) {
            return $this->success('Done!', $input, $output);
        } else {
            $output->writeln('');
            $output->writeln('<warn>Error</warn>: Your command line environment <warn>DOES NOT</warn> meet ActiveCollab requirements. Please correct the errors above and re-run this command.');
            $output->writeln('');

            return 1;
        }
    }
}
