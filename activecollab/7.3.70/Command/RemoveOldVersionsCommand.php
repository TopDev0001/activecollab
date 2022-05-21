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
use Versions;

/**
 * @package Angie\Command
 */
final class RemoveOldVersionsCommand extends Command
{
    public function configure()
    {
        parent::configure();

        $this->setDescription('Removing old versions of ActiveCollab');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (AngieApplication::isOnDemand()) {
            return $this->abort('This command is available only for self-hosted instances', 1, $input, $output);
        }

        try {
            $output->writeln('<info>OK</info>: Checking for old versions...');

            $versions_model = new Versions();
            $versions = $versions_model->scanVersionFolder();

            if (count($versions) > 1) {
                foreach ($versions as $version) {
                    if (!$version['current']) {
                        $path = realpath(ROOT . '/' . $version['version']);

                        $output->writeln('<info>OK</info> Removing version <comment>' . $version['version'] . '</comment>');

                        if (is_dir($path)) {
                            if (is_writable($path)) {
                                if (delete_dir($path)) {
                                    $output->writeln('<info>OK</info>: Version <comment>' . $version['version'] . '</comment> was successfully removed.');
                                } else {
                                    $output->writeln("<error>Error</error>: Can't delete <comment>$path</comment> directory");
                                }
                            } else {
                                $output->writeln('<error>Error</error>: Directory <comment>' . $path . '</comment> not writable');
                            }
                        } else {
                            $output->writeln('<error>Error</error>: Directory <comment>' . $path . '</comment> not found');
                        }
                    }
                }

                $check = $versions_model->checkOldVersions();

                if ($check['versions_is_ok']) {
                    return $this->success('Old versions have been removed successfully', $input, $output);
                } else {
                    return $this->abort('Failed to remove old versions. Please try again, or try to delate old version directories from <comment>' . ROOT . '</comment> directory', 1, $input, $output);
                }
            } else {
                return $this->success('Nothing to remove, only current version was found', $input, $output);
            }
        } catch (Exception $e) {
            return $this->abortDueToException($e, $input, $output);
        }
    }
}
