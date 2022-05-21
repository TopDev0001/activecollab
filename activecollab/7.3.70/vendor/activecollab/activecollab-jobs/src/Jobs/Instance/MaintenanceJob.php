<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use ActiveCollab\ConfigFile\ConfigFile;
use ActiveCollab\Logger\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;

abstract class MaintenanceJob extends Job
{
    /**
     * Construct a new Job instance.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        $data['instance_type'] = self::FEATHER;

        parent::__construct($data);
    }

    protected function runMigrations($instance_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $instance_id,
            'ondemand:migrate_up',
            'Migrations for #{account_id} have been ran',
            $logger
        );
    }

    protected function rebuildSearchIndex($instance_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $instance_id,
            'rebuild_search_index',
            'Search index for account #{account_id} has been rebuilt',
            $logger
        );
    }

    protected function persistWarehouseStoreId($instance_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $instance_id,
            'warehouse:persist_store_id',
            'Warehouse store ID for #{account_id} has been persisted',
            $logger
        );
    }

    protected function disconnectFromServices($instance_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $instance_id,
            'ondemand:disconnect_from_services',
            "Account #{$instance_id} has been disconnected from services",
            $logger
        );
    }

    protected function recalculateMrr($instance_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $instance_id,
            'ondemand:account:recalculate_mrr',
            "Account #{$instance_id} has recalculated Mrr",
            $logger
        );
    }

    protected function recordFastSpringOrder($install_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $install_id,
            'ondemand:billing:record_fast_spring_order',
            'FastSpring order and subscription are recorder into account billing paymnet method',
            $logger,
            ['latest' => true]
        );
    }

    protected function createAccountStorageDirectories($install_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $install_id,
            'ondemand:maintenance:create_account_storage_directories',
            'Upload and Thumbnails directories has been created.',
            $logger
        );
    }

    /**
     * Read current version of the application from version file.
     *
     * @param  int                  $instance_id
     * @param  string               $instance_path
     * @param  LoggerInterface|null $logger
     * @return mixed
     */
    protected function getCurrentVersion($instance_id, $instance_path, LoggerInterface $logger = null)
    {
        $version_file_path = $this->getVersionFilePath($instance_path);

        if (is_file($version_file_path)) {
            $config_file = new ConfigFile($version_file_path);

            if (!$config_file->optionExists('APPLICATION_VERSION')) {
                if ($logger) {
                    $logger->error(
                        'Failed to read current version of account #{account_id} because version number was not found in the version.php file',
                        $this->getLogContextArguments(
                            [
                                'account_id' => $instance_id,
                                'version_file_path' => $version_file_path,
                            ]
                        )
                    );
                }

                throw new RuntimeException(
                    sprintf('Option APPLICATION_VERSION not found in "%s"', $version_file_path)
                );
            }

            $account_app_version = $config_file->getOption('APPLICATION_VERSION');

            if ($logger) {
                $logger->info(
                    'Current version of account #{account_id} is {current_version}',
                    $this->getLogContextArguments(
                        [
                            'account_id' => $instance_id,
                            'current_version' => $account_app_version,
                            'version_file_path' => $version_file_path,
                        ]
                    )
                );
            }

            return $account_app_version;
        } else {
            if ($logger) {
                $logger->error(
                    'Failed to read current version of account #{account_id} because version files was not found',
                    $this->getLogContextArguments(
                        [
                            'account_id' => $instance_id,
                            'version_file_path' => $version_file_path,
                        ]
                    )
                );
            }

            throw new RuntimeException('Version file not found in the instance');
        }
    }

    protected function rebuildVersionFile($instance_id, $instance_path, $version, LoggerInterface $logger = null)
    {
        $file_content = "<?php\n\n";
        $file_content .= '  // Configuration file generated at ' . date('Y-m-d H:i:s') . " by ActiveCollab Jobs Consumer\n\n";
        $file_content .= '  const APPLICATION_VERSION = ' . var_export($version, true) . ";\n";

        $version_file_path = $this->getVersionFilePath($instance_path);

        if (!file_put_contents($version_file_path, $file_content)) {
            if ($logger) {
                $logger->error(
                    'Failed to write "{version}" into "{version_file}" for account #{account_id}',
                    $this->getLogContextArguments(
                        [
                            'version' => $version,
                            'version_file' => $version_file_path,
                            'account_id' => $instance_id,
                        ]
                    )
                );
            }

            throw new RuntimeException('Failed to write new version to the version.php file');
        }

        if ($logger) {
            $logger->info(
                'Version "{version}" successfully written into "{version_file}" file',
                $this->getLogContextArguments(
                    [
                        'version' => $version,
                        'version_file' => $version_file_path,
                        'account_id' => $instance_id,
                    ]
                )
            );
        }
    }

    /**
     * Return path of a version.php file for the given instance.
     *
     * @param  string $instance_path
     * @return string
     */
    protected function getVersionFilePath($instance_path)
    {
        return "{$instance_path}/config/version.php";
    }

    /**
     * Return true if clear routing cache command is available in the given version of ActiveCollab.
     *
     * This command was added in ActiveCollab 5.14.1.
     *
     * @param  string $current_version
     * @return bool
     */
    public function isClearRoutingCacheCommandAvailable($current_version)
    {
        return (bool) version_compare($current_version, '5.14.0', '>');
    }

    protected function migrateQuickbooksToken($instance_id, LoggerInterface $logger = null)
    {
        $this->runActiveCollabCliCommand(
            $instance_id,
            'integration:quickbooks_migrate_to_o_auth',
            'Quickbooks OAuth2 token migration for #{account_id} have been ran',
            $logger
        );
    }

    protected function runActiveCollabCliCommand(
        $instance_id,
        $command,
        $success_message = null,
        LoggerInterface $logger = null,
        $command_options = []
    ) {
        $command = new ExecuteActiveCollabCliCommand(
            [
                'instance_type' => 'feather',
                'instance_id' => $instance_id,
                'command' => $command,
                'command_options' => $command_options,
            ]
        );

        if ($this->hasContainer()) {
            $command->setContainer($this->getContainer());
        }

        $command->execute();

        if ($logger) {
            $logger->info(
                $success_message ? $success_message : 'Command "{command}" executed',
                $this->getLogContextArguments(
                    [
                        'account_id' => $instance_id,
                        'command' => $command,
                    ]
                )
            );
        }
    }

    /**
     * Enrich log context arguments with common upgrade job arguments, and return the array.
     *
     * @param  array $log_context
     * @return array
     */
    protected function getLogContextArguments(array $log_context)
    {
        return array_merge(
            [
                'maintenance_job_id' => $this->getQueueId(),
            ],
            $log_context
        );
    }

    /**
     * @return LoggerInterface|null
     */
    protected function getLogger()
    {
        return $this->hasContainer() && $this->getContainer()->has('log') ?
            $this->getContainer()->get('log') :
            $this->getLog();
    }
}
