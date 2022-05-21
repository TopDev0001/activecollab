<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateAddWebhookLog extends AngieModelMigration
{
    public function up()
    {
        if (AngieApplication::isOnDemand() || $this->tableExists('webhook_log')) {
            return;
        }

        $this->execute(
            "CREATE TABLE `webhook_log` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `instance_id` int(10) unsigned NOT NULL DEFAULT 0,
                    `webhook_id` int(10) unsigned NOT NULL DEFAULT 0,
                    `url` VARCHAR(512) NOT NULL DEFAULT '',
                    `payload` JSON,
                    `status` ENUM('pending','success','failure','exception') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
                    `sent_on` datetime DEFAULT NULL,
                    `request_time` int(10) unsigned DEFAULT NULL,
                    `status_code` int(10) unsigned DEFAULT NULL,
                    `response_phrase` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `instance_id` (`instance_id`),
                    KEY `webhook_id` (`webhook_id`),
                    KEY `url` (`url`),
                    KEY `status` (`status`),
                    KEY `request_status` (`instance_id`,`webhook_id`,`status`),
                    KEY `sent_on` (`sent_on`)
                ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        );
    }
}
