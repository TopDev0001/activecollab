<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * FeaturePointer class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
abstract class FeaturePointer extends BaseFeaturePointer
{
    public function shouldShow(User $user): bool
    {
        return empty(
            DB::executeFirstCell(
                'SELECT fp.id
                    FROM feature_pointers fp
                     LEFT JOIN feature_pointer_dismissals fpd ON fp.id = fpd.feature_pointer_id
                      WHERE (fp.expires_on IS NOT NULL AND fp.expires_on < ?)
                        OR (fpd.user_id = ? AND fp.type = ?)',
                new DateValue(),
                $user->getId(),
                get_class($this)
            )
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'parent_id' => $this->getParentId(),
            'description' => $this->getDescription(),
            'created_on' => $this->getCreatedOn(),
            'expires_on' => $this->getExpiresOn(),
        ];
    }
}
