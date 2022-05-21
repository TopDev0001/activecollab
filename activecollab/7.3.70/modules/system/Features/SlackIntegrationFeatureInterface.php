<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Features;

use Angie\Features\FeatureInterface;

interface SlackIntegrationFeatureInterface extends FeatureInterface
{
    const NAME = 'slack_integration';
    const VERBOSE_NAME = 'Slack Integration';
}
