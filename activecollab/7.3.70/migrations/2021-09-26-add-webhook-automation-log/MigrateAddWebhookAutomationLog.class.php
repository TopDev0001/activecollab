<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddWebhookAutomationLog extends AngieModelMigration
{
    public function up()
    {
        if (AngieApplication::isOnDemand() || $this->tableExists('webhook_automation_log')) {
            return;
        }

        $this->execute(
            "CREATE TABLE `webhook_automation_log` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `instance_id` int(10) unsigned NOT NULL DEFAULT 0,
                    `webhook_id` int(10) unsigned NOT NULL DEFAULT 0,
                    `automation` ENUM('high_to_normal','normal_to_low','low_to_normal','normal_to_high', 'disabled') COLLATE utf8mb4_unicode_ci NOT NULL,
                    `executed_at` datetime NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `instance_id` (`instance_id`),
                    KEY `webhook_id` (`webhook_id`),
                    KEY `webhook` (`instance_id`, `webhook_id`),
                    KEY `automation` (`automation`),
                    KEY `executed_at` (`executed_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }
}
