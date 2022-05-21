<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tracking\Utils\TrackingBillableStatusResolver;

use InvalidParamError;
use ITracking;
use ITrackingObject;
use IUser;
use Project;
use Task;

class TrackingBillableStatusResolver implements TrackingBillableStatusResolverInterface
{
    public function getBillabeStatus(IUser $user, ITracking $tracking_on, int $billable_status): int
    {
        if (!in_array($billable_status, ITrackingObject::TRACKING_BILLABLE_STATUSES)) {
            throw new InvalidParamError('billable_status', $billable_status);
        }

        $project = $tracking_on instanceof Task ? $tracking_on->getProject() : $tracking_on;

        // ensure that billable status is false if project budget is not billable
        if ($project->getBudgetType() === Project::BUDGET_NOT_BILLABLE) {
            return ITrackingObject::NOT_BILLABLE;
        }

        if (!$project->getMembersCanChangeBillable() && $user->isMember(true)) {
            return $project->isLeader($user) ? $billable_status : (int) $tracking_on->getIsBillable();
        }

        return $billable_status;
    }

    public function getBillabeStatusForTrackingObject(
        IUser $user,
        ITrackingObject $tracking_object,
        int $billable_status
    ): int
    {
        if (!in_array($billable_status, ITrackingObject::TRACKING_BILLABLE_STATUSES)) {
            throw new InvalidParamError('billable_status', $billable_status);
        }

        $parent = $tracking_object->getParent();
        $project = $parent instanceof Task ? $parent->getProject() : $parent;

        if ($project->getBudgetType() === Project::BUDGET_NOT_BILLABLE) {
            return $tracking_object->getBillableStatus();
        }

        if (!$project->getMembersCanChangeBillable() && $user->isMember(true)) {
            return $project->isLeader($user) ? $billable_status : $tracking_object->getBillableStatus();
        }

        return $billable_status;
    }
}
