<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Utils\Webhooks\Transformator\ZapierWebhookPayloadTransformator;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Inflector;

AngieApplication::useController('integration_singletons', SystemModule::NAME);

class ZapierIntegrationController extends IntegrationSingletonsController
{
    /**
     * @var ZapierIntegration
     */
    protected $integration;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->integration = Integrations::findFirstByType(ZapierIntegration::class);

        if (!$this->integration instanceof ZapierIntegration) {
            return Response::CONFLICT;
        }
    }

    /**
     * Return account url and token.
     *
     * @return array|int
     */
    public function get_data(Request $request, User $user)
    {
        if ($this->integration->isInUse($user)) {
            return $this->integration->getDataForUser($user);
        }

        return Response::FORBIDDEN;
    }

    /**
     * Enable integrations.
     *
     * @return ZapierIntegration|int
     */
    public function enable(Request $request, User $user)
    {
        if ($user) {
            return $this->integration->enable($user);
        }

        return Response::FORBIDDEN;
    }

    /**
     * Disable integration.
     *
     * @return ZapierIntegration|int
     */
    public function disable(Request $request, User $user)
    {
        if ($user) {
            return $this->integration->disable($user);
        }

        return Response::FORBIDDEN;
    }

    /**
     * Return most recent object of the requested type.
     *
     * @return array|int
     */
    public function payload_example(Request $request, User $user)
    {
        if ($this->integration->isInUse($user)) {
            $payload_transformator = new ZapierWebhookPayloadTransformator();

            try {
                $event_type = $this->getValidEventTypeFromSlug($request->get('event_type'), $payload_transformator);
            } catch (Exception $e) {
                return Response::NOT_FOUND;
            }

            $most_recent_object = $this->getMostRecentObjectByEventType($event_type);

            $result = [];

            if ($most_recent_object instanceof DataObject) {
                $result[] = $payload_transformator->transform($event_type, $most_recent_object);
            }

            return $result;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Resolve and validate event type from URL argument.
     *
     * @param  string $slug
     * @return string
     */
    private function getValidEventTypeFromSlug($slug, WebhookPayloadTransformatorInterface $payload_transformator)
    {
        $event_type = Inflector::camelize(str_replace('-', '_', $slug));

        if (!in_array($event_type, $payload_transformator->getSupportedEvents())) {
            throw new InvalidArgumentException("Event type '$event_type' is not supported.");
        }

        return $event_type;
    }

    /**
     * Return most recent object instance by the given object type.
     *
     * @param  string                     $most_recent_object_type
     * @return Dataobject[]|DBResult|null
     */
    private function getMostRecentObjectByEventType($most_recent_object_type)
    {
        $conditions = null;
        $order = 'created_on DESC';

        switch ($most_recent_object_type) {
            case 'ProjectCreated':
                $manager_class = Projects::class;

                break;
            case 'TaskListCreated':
                $manager_class = TaskLists::class;

                break;
            case 'TaskCreated':
            case 'TaskListChanged':
            case 'TaskCompleted':
                $manager_class = Tasks::class;

                if ($most_recent_object_type === 'TaskCompleted') {
                    $conditions = 'completed_on IS NOT NULL';
                    $order = 'completed_on DESC, created_on DESC';
                }

                break;
            case 'CommentCreated':
                $manager_class = Comments::class;

                break;
            case 'TimeRecordCreated':
                $manager_class = TimeRecords::class;

                break;
        }

        if (!empty($manager_class)) {
            return call_user_func(
                "{$manager_class}::findOne",
                [
                    'conditions' => $conditions,
                    'order' => $order,
                ]
            );
        }

        return null;
    }
}
