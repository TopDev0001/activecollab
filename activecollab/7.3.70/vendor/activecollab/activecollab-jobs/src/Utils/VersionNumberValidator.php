<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Utils;

class VersionNumberValidator implements VersionNumberValidatorInterface
{
    public function isValidVersionNumber($version)
    {
        if (is_string($version) && $version) {
            $bits = explode('.', $version);

            if (count($bits) === 3) {
                foreach ($bits as $bit) {
                    if (!ctype_digit($bit)) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }
}
