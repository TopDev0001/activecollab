<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Features\InvoicesFeatureInterface;

function invoicing_handle_on_morning_mail()
{
    if (AngieApplication::featureFactory()->makeFeature(InvoicesFeatureInterface::NAME)->isEnabled()) {
        InvoiceOverdueReminders::send();
    }
}
