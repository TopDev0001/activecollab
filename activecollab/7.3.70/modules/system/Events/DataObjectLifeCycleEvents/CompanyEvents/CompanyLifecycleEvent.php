<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Events\DataObjectLifeCycleEvents\CompanyEvents;

use ActiveCollab\Foundation\Events\DataObjectLifeCycleEvent\DataObjectLifeCycleEvent;
use Company;

class CompanyLifecycleEvent extends DataObjectLifeCycleEvent implements CompanyLifecycleEventInterface
{
    public function __construct(Company $company)
    {
        parent::__construct($company);
    }
}
