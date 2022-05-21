<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * BudgetThreshold class.
 *
 * @package ActiveCollab.modules.tracking
 * @subpackage models
 */
final class BudgetThreshold extends BaseBudgetThreshold
{
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'project_id' => $this->getProjectId(),
            'type' => $this->getType(),
            'threshold' => $this->getThreshold(),
            'created_on' => $this->getCreatedOn(),
            'created_by_id' => $this->getCreatedById(),
            'created_by_name' => $this->getCreatedByName(),
            'created_by_email' => $this->getCreatedByEmail(),
            'is_notification_sent' => $this->getIsNotificationSent(),
            'notification_sent_on' => $this->getNotificationSentOn(),
            'updated_on' => $this->getUpdatedOn(),
        ];
    }
}
