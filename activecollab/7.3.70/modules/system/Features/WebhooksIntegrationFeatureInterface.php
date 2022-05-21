<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\FeatureInterface;

interface WebhooksIntegrationFeatureInterface extends FeatureInterface
{
    const NAME = 'webhooks_integration';
    const VERBOSE_NAME = 'Webhooks Integration';
}
