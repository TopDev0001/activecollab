<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\Features\WebhooksIntegrationFeatureInterface;
use Angie\Events;
use Angie\Inflector;
use Angie\Utils\FeatureStatusResolver\FeatureStatusResolverInterface;

class Webhooks extends BaseWebhooks
{
    const ENABLED = 'enabled';
    const DISABLED = 'disabled';

    public static function create(
        array $attributes,
        bool $save = true,
        bool $announce = true
    ): Webhook
    {
        if (!empty($attributes['integration_type'])) {
            $parent_integratrion = self::getParentIntegration($attributes['integration_type']);

            if ($parent_integratrion instanceof Integration) {
                $attributes['integration_id'] = $parent_integratrion->getId();
            }
        }

        if (empty($attributes['integration_id'])) {
            $attributes['integration_id'] = Integrations::findFirstByType('WebhooksIntegration')->getId();
        }

        if (empty($attributes['type'])) {
            $attributes['type'] = Webhook::class;
        }

        return parent::create($attributes, $save, $announce);
    }

    // ---------------------------------------------------
    //  Permissions
    // ---------------------------------------------------

    public static function canAdd(User $user): bool
    {
        return $user->isOwner() && self::isWebhooksFeatureEnabled();
    }

    /**
     * Returns true if $user can edit existing webhhoks.
     *
     * @return bool
     */
    public static function canEdit(User $user)
    {
        return $user->isOwner();
    }

    /**
     * Returns true if $user can delete existing webhooks.
     *
     * @return bool
     */
    public static function canDelete(User $user)
    {
        return $user->isOwner();
    }

    // ---------------------------------------------------
    //  Collections
    // ---------------------------------------------------

    /**
     * Returns webhooks collection.
     *
     * Expected collection names:
     *  - all (returns all webhooks)
     *  - all_enabled (returns all enabled webhooks)
     *  - webhooks_integration (returns all webhooks related to this integration)
     *  - webhooks_integration_enabled (returns all enabled webhooks related to this integration)
     *  - webhooks_integration_disabled (returns all disabled webhooks related to this integration)
     *
     * @param  User|null         $user
     * @return ModelCollection
     * @throws InvalidParamError
     */
    public static function prepareCollection(string $collection_name, $user)
    {
        $collection = parent::prepareCollection($collection_name, $user);
        $conditions = [];
        if (strpos($collection_name, 'integration') !== false) {
            $integration = self::getIntegrationByCollectionName($collection_name);
            $conditions[] = DB::prepareConditions(['integration_id = ?', $integration->getId()]);
        } elseif (strpos($collection_name, DataManager::ALL) === false) {
            throw new InvalidParamError('collection_name', $collection_name);
        }

        if (str_ends_with($collection_name, self::ENABLED)) {
            $conditions[] = DB::prepareConditions(['is_enabled = ?', true]);
        } elseif (str_ends_with($collection_name, self::DISABLED)) {
            $conditions[] = DB::prepareConditions(['is_enabled =? ', false]);
        }

        // merge conditions
        if (!empty($conditions)) {
            $collection->setConditions(implode(' AND ', $conditions));
        }

        return $collection;
    }

    /**
     * Return all enabled webhooks.
     *
     * @return iterable|DBResult|Webhook[]
     */
    public static function findEnabled(): ?iterable
    {
        return self::find(
            [
                'conditions' => ['`is_enabled` = ?', true],
            ]
        );
    }

    /**
     * Return all enabled webhooks for an integration.
     *
     * @return DBResult|Webhook[]
     */
    public static function findEnabledForIntegration(IntegrationInterface $integration): ?iterable
    {
        return self::find(
            [
                'conditions' => [
                    '`integration_id` = ? AND `is_enabled` = ?',
                    $integration->getId(),
                    true,
                ],
            ]
        );
    }

    public static function countEnabledForIntegration(IntegrationInterface $integration): int
    {
        return self::count(
            [
                '`integration_id` = ? AND `is_enabled` = ?',
                $integration->getId(),
                true,
            ]
        );
    }

    // ---------------------------------------------------
    //  Integration resolvers
    // ---------------------------------------------------

    /**
     * Return parent integration by type.
     *
     * @param $integration_type
     * @return Integration
     */
    public static function getParentIntegration($integration_type)
    {
        $integration = Integrations::findFirstByType(Inflector::camelize($integration_type), false);

        return !empty($integration) ? $integration : Integrations::findFirstByType('WebhooksIntegration');
    }

    /**
     * Return integration by collection name.
     *
     * @param $collection_name
     * @return Integration
     */
    private static function getIntegrationByCollectionName($collection_name)
    {
        $bits = explode('_', $collection_name);
        $name = $bits[0] . '_' . $bits[1];

        return self::getParentIntegration($name);
    }

    /**
     * @return WebhookPayloadTransformatorInterface[]|array
     */
    public static function getPayloadTransformators(): array
    {
        /** @var WebhookPayloadTransformatorInterface[] $available_transformators */
        $available_transformators = [];
        Events::trigger('on_available_webhook_payload_transformators', [&$available_transformators]);

        $result = [];
        foreach ($available_transformators as $available_transformator) {
            $result[] = new $available_transformator();
        }

        return $result;
    }

    private static function isWebhooksFeatureEnabled(): bool {
        $feature_status_resolver = AngieApplication::getContainer()->get(FeatureStatusResolverInterface::class);
        $webhook_feature = AngieApplication::featureFactory()->makeFeature(WebhooksIntegrationFeatureInterface::NAME);

        return $feature_status_resolver->isEnabled($webhook_feature);
    }
}
