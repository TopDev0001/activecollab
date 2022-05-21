<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncementsLoader;

class NewFeatureAnnouncementsLoader implements NewFeatureAnnouncementsLoaderInterface
{
    private $new_feature_announcements;

    public function __construct(array $new_feature_announcements)
    {
        $this->new_feature_announcements = $new_feature_announcements;
    }

    public function getNewFeatureAnnouncements(): array
    {
        return $this->new_feature_announcements;
    }
}
