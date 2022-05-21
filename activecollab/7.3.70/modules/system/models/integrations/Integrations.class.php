<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Events;

/**
 * Integrations class.
 *
 * @package ActiveCollab.modules.system
 * @subpackage models
 */
class Integrations extends BaseIntegrations
{
    /**
     * Return integrations that are available for the given user.
     *
     * @return array
     */
    public static function getFor(User $user)
    {
        /** @var Integration[] $available_integrations */
        $available_integrations = [];

        Events::trigger(
            'on_available_integrations',
            [
                &$available_integrations,
                &$user,
            ]
        );

        $result = [];

        foreach ($available_integrations as $available_integration) {
            $result[] = [
                'name' => $available_integration->getName(),
                'class' => 'Integration',
                'short_name' => $available_integration->getShortName(),
                'description' => $available_integration->getDescription(),
                'is_singleton' => $available_integration->isSingleton(),
                'is_in_use' => $available_integration->isInUse($user),
                'is_third_party' => $available_integration->isThirdParty(),
                'group' => $available_integration->getGroup(),
                'group_order' => $available_integration->getGroupOrder(),
                'already_connected' => self::findExistingForUser($user, $available_integration),
                'open_action_name' => $available_integration->getOpenActionName(),
                'is_visible' => $available_integration->isVisible(),
            ];
        }

        if (count($result)) {
            usort(
                $result,
                function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                }
            );
        }

        return $result;
    }

    /**
     * Find existing instances of a give integration for a given user.
     *
     * @return Integration[]
     */
    private static function findExistingForUser(User $user, Integration $integration)
    {
        if ($integration->isSingleton()) {
            return [self::findFirstByType(get_class($integration))];
        } else {
            $instances = self::findByUserAndType($user, get_class($integration));

            if (empty($instances)) {
                $instances = [];
            }

            return $instances;
        }
    }

    /**
     * Return first instance of the given integration type.
     *
     * @param  string                      $type
     * @param  bool                        $create_when_not_found
     * @return Integration|DataObject|null
     */
    public static function findFirstByType($type, $create_when_not_found = true)
    {
        $instance = self::findOneBySql('SELECT * FROM `integrations` WHERE `type` = ? ORDER BY `id` LIMIT 0, 1', $type);

        if (empty($instance) && $create_when_not_found) {
            $instance = self::create(
                [
                    'type' => $type,
                ]
            );
        }

        return $instance;
    }

    /**
     * Return all instances of the given integration type that are used by the given user.
     *
     * @param  string                      $type
     * @return DbResult|Integration[]|null
     */
    public static function findByUserAndType(User $user, $type)
    {
        return self::findBy(
            [
                'type' => $type,
                'created_by_id' => $user->getId(),
            ]
        );
    }
}
