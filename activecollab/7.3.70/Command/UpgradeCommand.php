<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Command;

use ActiveCollab\Foundation\App\Mode\ApplicationModeInterface;
use Angie\Command\Command;
use AngieApplication;
use AngieModelMigration;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class UpgradeCommand extends Command
{
    public function configure()
    {
        parent::configure();

        $this
            ->setDescription('Upgrade to the latest available version')
            ->addOption(
                'dont-download-latest',
                '',
                InputOption::VALUE_NONE,
                "Don't check for the latest release"
            )
            ->addOption(
                'dont-backup-database',
                '',
                InputOption::VALUE_NONE,
                "Don't create a database backup"
            )
            ->addOption(
                'debug-from-version',
                '',
                InputOption::VALUE_REQUIRED,
                'Make the system belive that it is running this version (development mode only)'
            )
            ->addOption(
                'debug-to-version',
                '',
                InputOption::VALUE_REQUIRED,
                'Skip auto upgrade check, and try to upgrade to this version (development mode only)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $debug_upgrade_from_version = $this->getDebugFromVersion($input);

        if (AngieApplication::isOnDemand() && empty($debug_upgrade_from_version)) {
            return $this->abort(
                'This command is available only for self-hosted instances',
                1,
                $input,
                $output
            );
        }

        $current_version = $debug_upgrade_from_version ?? AngieApplication::getVersion();

        if ($current_version == 'current') {
            return $this->upgradeDevelopmentInstance($current_version, $input, $output);
        } else {
            try {
                if ($input->getOption('dont-download-latest')) {
                    $output->writeln('<info>OK:</info> Skipping latest release check');
                } else {
                    $output->writeln('<info>OK:</info> Checking for latest release');

                    $debug_upgrade_to_version = $this->getDebugToVersion($input);

                    if ($debug_upgrade_to_version) {
                        $latest_version = $debug_upgrade_to_version;
                    } else {
                        AngieApplication::autoUpgrade()->checkForUpdates();

                        $latest_version = AngieApplication::autoUpgrade()->getLatestAvailableVersion();
                    }

                    $output->writeln(
                        sprintf('<info>OK:</info> Latest release is <comment>%s v%s</comment> (you have installed v%s)',
                            AngieApplication::getName(),
                            $latest_version,
                            $current_version
                        )
                    );

                    if (version_compare($latest_version, $current_version) > 0) {
                        $phar_path = $this->downloadLatestRelease($latest_version, $output);

                        AngieApplication::autoUpgrade()->unpackPhar(
                            $phar_path,
                            $latest_version,
                            function ($unpack_phar_path) use ($phar_path, $output) {
                                $output->writeln("<info>OK:</info> Unpacking '$phar_path' to '$unpack_phar_path'");
                            },
                            function ($unpack_phar_path) use ($output) {
                                $output->writeln("<info>OK:</info> Upgrade package extracted to '$unpack_phar_path'");
                            }
                        );
                    }
                }

                if (empty($latest_version)) {
                    $latest_version = AngieApplication::autoUpgrade()->getLatestDownloadedVersion();
                }

                if (version_compare($latest_version, $current_version) > 0) {
                    $output->writeln('<info>OK:</info> Upgrading <comment>' . AngieApplication::getName() . " v{$current_version}</comment> to <comment>v{$latest_version}</comment>");

                    $this->includeLatestUpgradeClasses($output);

                    if ($this->isEnvironmentGood($output) && $this->canMigrate($latest_version, $output)) {
                        $this->backupDatabase($input, $output);
                        $this->runMigrations($latest_version, $output);
                        $this->copyAssets($latest_version, $output);
                        $this->updateVersionFile($latest_version, $output);

                        return $this->success('Done! Enjoy the all new ' . AngieApplication::getName() . '.', $input, $output);
                    } else {
                        return $this->abort('System requirements not met', 1, $input, $output);
                    }
                } else {
                    $output->writeln('<info>OK:</info> No new version found. Assets will be refreshed, and migrations checked');

                    $this->includeLatestUpgradeClasses($output);

                    if ($this->isEnvironmentGood($output) && $this->canMigrate($current_version, $output)) {
                        $this->backupDatabase($input, $output);
                        $this->runMigrations($current_version, $output);
                        $this->copyAssets($current_version, $output);

                        return $this->success('Done!', $input, $output);
                    } else {
                        return $this->abort('System requirements not met', 1, $input, $output);
                    }
                }
            } catch (Exception $e) {
                return $this->abortDueToException($e, $input, $output);
            }
        }
    }

    private function upgradeDevelopmentInstance(
        string $current_version,
        InputInterface $input,
        OutputInterface $output
    ): int
    {
        $output->writeln('<info>OK:</info> This is development instance. Assets will be refreshed, and migrations checked');

        $this->includeLatestUpgradeClasses($output);

        $this->runMigrations($current_version, $output);
        $this->copyAssets($current_version, $output);

        return $this->success('Done', $input, $output);
    }

    /**
     * Download latest release.
     *
     * @param  string $download_version
     * @return string
     */
    private function downloadLatestRelease($download_version, OutputInterface $output)
    {
        $output->writeln('');

        $progress = new ProgressBar($output, 100);
        $progress->start();

        return AngieApplication::autoUpgrade()->downloadRelease(
            $download_version,
            sprintf('%s/%s.phar.gz', WORK_PATH, $download_version),
            function ($percent) use (&$progress) {
                $progress->setProgress($percent);
            }, function ($file_path, $headers) use (&$output, &$progress) {
                $progress->finish();
                $output->writeln('');
                $output->writeln('');

                if ($output->getVerbosity()) {
                    $output->writeln(
                        sprintf(
                            "<info>OK:</info> File downloaded to '%s' (MD5 cheksum: <comment>%s</comment>)",
                            $file_path,
                            $headers['x-autoupgrade-package-hash']
                        )
                    );
                }
            }
        );
    }

    /**
     * Include latest upgrade classes.
     */
    private function includeLatestUpgradeClasses(OutputInterface $output)
    {
        AngieApplication::autoUpgrade()->includeLatestUpgradeClasses(
            function ($angie_path) use ($output) {
                $output->writeln("<info>OK:</info> Included migration classes from '$angie_path'");
            }
        );
    }

    /**
     * Return true if environment check returns true.
     *
     * @return bool
     */
    private function isEnvironmentGood(OutputInterface $output)
    {
        return AngieApplication::autoUpgrade()->checkEnvironment(
            function ($message) use (&$output) {
                $output->writeln("<info>OK:</info> $message");
            }, function ($message) use (&$output) {
                $output->writeln("<error>Error:</error> $message");
            }
        );
    }

    /**
     * Return true if we can migrate.
     *
     * @param  string $latest_version
     * @return bool
     */
    private function canMigrate($latest_version, OutputInterface $output)
    {
        return AngieApplication::autoUpgrade()->canMigrate(
            $latest_version,
            function (AngieModelMigration $migration, $reason) use (&$output) {
                $output->writeln("Migration '" . get_class($migration) . "' can't be executed. Reason: $reason");
            }
        );
    }

    /**
     * Backup database.
     */
    private function backupDatabase(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dont-backup-database')) {
            $output->writeln('<info>OK</info>: Database backup skipped.');
        } else {
            AngieApplication::autoUpgrade()->backupDatabase(
                WORK_PATH,
                function ($file) use (&$output) {
                    $output->writeln("<info>OK:</info> Creating a database backup to '$file'");
                }, function ($file) use (&$output) {
                    $output->writeln("<info>OK:</info> Database backed up to '$file'");
                }
            );
        }
    }

    /**
     * Run migrations and clear cache.
     *
     * @param string $latest_version
     */
    private function runMigrations($latest_version, OutputInterface &$output)
    {
        AngieApplication::autoUpgrade()->runMigrations(
            $latest_version,
            function ($message) use (&$output) {
                $output->writeln("<info>OK:</info> $message");
            },
            function () use (&$output) {
                $output->writeln('<info>OK:</info> Migrations executed');
            }
        );
    }

    /**
     * Run migrations and clear cache.
     *
     * @param string $latest_version
     */
    private function copyAssets($latest_version, OutputInterface &$output)
    {
        $env_len = strlen(ENVIRONMENT_PATH);

        AngieApplication::autoUpgrade()->copyAssetsToPublicDirectory(
            $latest_version,
            function ($target_path) use (&$output, $env_len) {
                $output->writeln('<info>OK:</info> Assets path <comment>' . substr($target_path, $env_len + 1) . '</comment> cleared of old files');
            },
            function ($source, $target) use (&$output, $env_len) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln('<info>OK:</info> File <comment>' . substr($source, $env_len + 1) . '</comment> copied to <comment>' . substr($target, $env_len + 1) . '</comment>');
                }
            },
            function ($message) use (&$output) {
                $output->writeln("<info>OK:</info> $message");
            }
        );
    }

    /**
     * Update version file.
     *
     * @param string $latest_version
     */
    private function updateVersionFile($latest_version, OutputInterface $output)
    {
        AngieApplication::autoUpgrade()->updateVersionFile(
            $latest_version,
            function () use (&$output) {
                $output->writeln("<info>OK:</info> Updated '/config/version.php' file");
            }
        );
    }

    private function getDebugFromVersion(InputInterface $input): ?string
    {
        return $this->getDebugVersionFromInput($input, 'debug-from-version');
    }

    private function getDebugToVersion(InputInterface $input): ?string
    {
        return $this->getDebugVersionFromInput($input, 'debug-to-version');
    }

    private function getDebugVersionFromInput(InputInterface $input, string $option_name): ?string
    {
        $debug_version = $input->getOption($option_name);

        if ($debug_version && !AngieApplication::getContainer()->get(ApplicationModeInterface::class)->isInDevelopment()) {
            throw new RuntimeException('Debug version option can be used only in development mode.');
        }

        return $debug_version;
    }
}
