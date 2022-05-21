<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class MigrateRenameWebhookAutomationExecutedField extends AngieModelMigration
{
    public function up()
    {
        if (AngieApplication::isOnDemand() || !$this->tableExists('webhook_automation_log')) {
            return;
        }

        $webhook_automation_log = $this->useTableForAlter('webhook_automation_log');

        if (!$webhook_automation_log->getColumn('executed_at')) {
            $webhook_automation_log->addColumn(
                new DBDateTimeColumn('executed_at'),
                'automation'
            );
        }

        if (!$webhook_automation_log->getIndex('executed_at')) {
            $webhook_automation_log->addIndex(new DBIndex('executed_at'));
        }

        if ($webhook_automation_log->getIndex('sent_on')) {
            $webhook_automation_log->dropIndex('sent_on');
        }

        if ($webhook_automation_log->getIndex('executed_on')) {
            $webhook_automation_log->dropIndex('executed_on');
        }

        if ($webhook_automation_log->getColumn('executed_on')) {
            $webhook_automation_log->dropColumn('executed_on');
        }
    }
}
