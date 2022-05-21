<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Instance;

use ActiveCollab\ActiveCollabJobs\Utils\VersionNumberValidator;
use Exception;
use InvalidArgumentException;

class Upgrade extends MaintenanceJob
{
    public function __construct(array $data = null)
    {
        $version = !empty($data['version']) ? $data['version'] : null;

        if (!(new VersionNumberValidator())->isValidVersionNumber($version)) {
            throw new InvalidArgumentException('Valid version number is required');
        }

        if (empty($data['rebuild_search_index'])) {
            $data['rebuild_search_index'] = false;
        }

        parent::__construct($data);
    }

    public function execute()
    {
        $logger = $this->getLogger();

        $instance_id = $this->getData('instance_id');
        $version = $this->getData('version');

        // Get current application version.
        $current_app_version = $this->shepherd_account_connection->executeFirstCell('SELECT `version` FROM `applications` WHERE `account_id`=?', $instance_id);

        // Check if we should proceed with the upgrade, or we already have the latest version.
        if (!$this->shouldUpgrade($current_app_version, $version)) {
            if ($logger) {
                $logger->info(
                    'Current version of account #{account_id} is {current_version}, so there is no reason to upgrade to {version}',
                    $this->getLogContextArguments(
                        [
                            'account_id' => $instance_id,
                            'current_version' => $current_app_version,
                            'version' => $version,
                        ]
                    )
                );
            }

            return;
        }

        try {
            ob_start();
            $this->shepherd_account_config->setVersion($instance_id, $version);

            // Run database migrations.
            $this->runActiveCollabCliCommand(
                $instance_id,
                'ondemand:migrate_up',
                'Database migrations for account #{account_id} have been executed',
                $logger
            );

            // Clear routing cache.
            if ($this->isClearRoutingCacheCommandAvailable($current_app_version)) {
                $this->runActiveCollabCliCommand(
                    $instance_id,
                    'clear_routing_cache',
                    'Routing cache for account #{account_id} has been cleared',
                    $logger
                );
            }

            $rebuild_search_index = $this->getData('rebuild_search_index');

            // Optionally rebuild search index.
            if ($rebuild_search_index) {
                $this->rebuildSearchIndex($instance_id, $logger);
            }

            $output = ob_get_clean();

            if ($logger) {
                $logger->info(
                    'Account #{account_id} has been successfully upgraded to {version}',
                    $this->getLogContextArguments(
                        [
                            'version' => $version,
                            'account_id' => $instance_id,
                            'rebuild_search_index' => $rebuild_search_index,
                            'output' => $output,
                        ]
                    )
                );
            } else {
                print $output . "\n";
            }
        } catch (Exception $e) {
            if (!empty(ob_get_status())) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    public function shouldUpgrade(string $current_app_version, string $upgrade_to_version): bool
    {
        return version_compare($upgrade_to_version, $current_app_version, '>');
    }
}
