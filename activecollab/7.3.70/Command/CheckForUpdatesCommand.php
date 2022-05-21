<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use Angie\Command\Command;
use AngieApplication;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CheckForUpdatesCommand extends Command
{
    public function configure()
    {
        parent::configure();

        $this->setDescription('Check for updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (AngieApplication::isOnDemand()) {
            return $this->abort('This command is available only for self-hosted instances', 1, $input, $output);
        }

        try {
            $output->writeln('<info>OK:</info> Checking for updates');

            AngieApplication::autoUpgrade()->checkForUpdates();

            $current_version = AngieApplication::getVersion();

            if ($current_version === 'current') {
                $current_version = '1.1.5';
            }

            $latest_stable_version = AngieApplication::autoUpgrade()->getLatestStableVersion();
            $latest_available_version = AngieApplication::autoUpgrade()->getLatestAvailableVersion();

            $support_subscription_expires_on = AngieApplication::autoUpgrade()->getSupportSubscriptionExpiresOn();
            $support_subscription_expires_on = $support_subscription_expires_on ? date('Y-m-d', $support_subscription_expires_on) : '- invalid -';

            if (version_compare($current_version, $latest_available_version, '<')) {
                if ($latest_stable_version != $latest_available_version) {
                    $output->writeln('');
                    $output->writeln('<question>Support renewal needed</question>');
                    $output->writeln('');
                    $output->writeln("Your Supprot and Upgrades subscriptions <comment>expired on $support_subscription_expires_on</comment> and you have access to <comment>$latest_available_version</comment>.");
                    $output->writeln("To get access to <comment>$latest_stable_version</comment> you need to renew your Support and Upgrades subscription here:");
                    $output->writeln('');
                    $output->writeln(AngieApplication::autoUpgrade()->getRenewSupportUrl());
                }

                $output->writeln('');
                $output->writeln('<question>Upgrade needed</question>');
                $output->writeln('');
                $output->writeln("Your current version is <comment>$current_version</comment>, and latest stable version is <comment>$latest_stable_version</comment>.");

                $output->writeln("Please use <comment>upgrade</comment> command or web interface to upgrade to <comment>$latest_available_version</comment>.");
                $output->writeln('Detailed upgrade instructions can be found here:');
                $output->writeln('');
                $output->writeln(AngieApplication::autoUpgrade()->getUpgradeInstructionsUrl());
                $output->writeln('');
            } else {
                if (version_compare($current_version, $latest_available_version, '>')) {
                    $output->writeln('');
                    $output->writeln('<error>License error</error>');
                    $output->writeln('');
                    $output->writeln("You run $current_version, while your license grants you access to releases up to version $latest_available_version.");
                    $output->writeln("If you think that there's an error with our records, please contact support@activecollab.com.");
                    $output->writeln('');
                    $output->writeln('This incident has been reported.');
                    $output->writeln('');
                } else {
                    $output->writeln("<info>OK:</info> You run $current_version");
                }
            }

            return 0;
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
