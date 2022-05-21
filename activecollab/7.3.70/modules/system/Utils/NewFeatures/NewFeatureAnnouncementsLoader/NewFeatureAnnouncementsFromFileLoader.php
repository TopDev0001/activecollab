<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncementsLoader;

use ActiveCollab\Module\System\Utils\NewFeatures\NewFeatureAnnouncement;
use RuntimeException;

class NewFeatureAnnouncementsFromFileLoader extends NewFeatureAnnouncementsLoader
{
    public function __construct(string $new_features_file_path)
    {
        if (is_file($new_features_file_path)) {
            $loaded_from_file = [];

            $new_feature_announcements = require_once $new_features_file_path;

            if (is_array($new_feature_announcements)) {
                foreach ($new_feature_announcements as $new_feature_announcement) {
                    if ($new_feature_announcement instanceof NewFeatureAnnouncement) {
                        $loaded_from_file[] = $new_feature_announcement;
                    }
                }
            }

            parent::__construct($loaded_from_file);
        } else {
            throw new RuntimeException("Can't find file '{$new_features_file_path}'.");
        }
    }
}
