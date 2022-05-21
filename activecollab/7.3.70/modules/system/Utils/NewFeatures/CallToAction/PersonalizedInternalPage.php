<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures\CallToAction;

use User;

class PersonalizedInternalPage extends InternalPage implements PersonalizerCallToActionInterface
{
    public function getPersonalizedUrl(User $user)
    {
        return str_replace(
            ':user_id',
            (string) $user->getId(),
            $this->getUrl()
        );
    }
}
