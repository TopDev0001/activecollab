<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Model\FeaturePointer\FeaturePointerInterface;

/**
 * FeaturePointers class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class FeaturePointers extends BaseFeaturePointers
{
    public static function prepareCollection(string $collection_name, $user)
    {
        if (!$user) {
            throw new InvalidParamError('user', $user, '$user is required to be a user');
        }

        if (str_starts_with($collection_name, 'feature_pointers_for_user')) {
            return self::prepareFeaturePointersForUser($collection_name, $user);
        }

        throw new InvalidParamError('collection_name', $collection_name, '$collection_name is not valid');
    }

    private static function prepareFeaturePointersForUser($collection_name, User $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);

        if ($collection->count() > 0) {
            /** @var FeaturePointer[] $feature_pointers */
            $feature_pointers = $collection->execute();

            $visible_pointers_ids = [];

            foreach ($feature_pointers as $feature_pointer) {
                if ($feature_pointer->shouldShow($user)) {
                    $visible_pointers_ids[] = $feature_pointer->getId();
                }
            }

            if (count($visible_pointers_ids)) {
                $collection->setConditions('id IN (?)', $visible_pointers_ids);
            } else {
                $collection->setConditions('id < 1');
            }
        }

        return $collection;
    }

    public static function dismiss(FeaturePointerInterface $feature_pointer, IUser $user)
    {
        try {
            DB::beginWork('Dismiss user feature pointer @ ' . __CLASS__);

            DB::insertRecord('feature_pointer_dismissals', [
                'feature_pointer_id' => $feature_pointer->getId(),
                'user_id' => $user->getId(),
            ]);

            DB::commit('User feature pointer dismissed @ ' . __CLASS__);
        } catch (Exception $e) {
            DB::rollback('Failed to dismiss user feature pointer @ ' . __CLASS__);
            throw $e;
        }
    }
}
