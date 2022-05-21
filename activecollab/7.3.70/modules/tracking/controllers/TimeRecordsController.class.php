<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\Tracking\Utils\TimeRecordSourceResolver\TimeRecordSourceResolverInterface;
use ActiveCollab\Module\Tracking\Utils\TrackingBillableStatusResolver\TrackingBillableStatusResolverInterface;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('project', SystemModule::NAME);

final class TimeRecordsController extends ProjectController
{
    /**
     * Selected time record instance.
     *
     * @var TimeRecord
     */
    protected $active_time_record;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        if (!$this->active_project->getIsTrackingEnabled()) {
            return Response::NOT_FOUND;
        }

        $this->active_time_record = DataObjectPool::get(
            TimeRecord::class,
            $request->getId('time_record_id')
        );

        if (empty($this->active_time_record)) {
            $this->active_time_record = new TimeRecord();
            $this->active_time_record->setParent($this->active_project);
        }

        if (!$this->active_time_record->getProject()->is($this->active_project)) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * List project time records.
     *
     * @return ModelCollection|int
     */
    public function index(Request $request, User $user)
    {
        if ($user instanceof Client && !$this->active_project->getIsClientReportingEnabled()) {
            return Response::NOT_FOUND;
        }

        AccessLogs::logAccess($this->active_project, $user);

        return TimeRecords::prepareCollection('time_records_in_project_' . $this->active_project->getId() . '_page_' . $request->getPage(), $user);
    }

    /**
     * @return ModelCollection|int
     */
    public function filtered_by_date(Request $request, User $user)
    {
        if ($user instanceof Client && !$this->active_project->getIsClientReportingEnabled()) {
            return Response::NOT_FOUND;
        }

        $from_string = $request->get('from');
        $to_string = $request->get('to');

        $from = $from_string ? DateValue::makeFromString($from_string) : null;
        $to = $to_string ? DateValue::makeFromString($to_string) : null;

        if ($from instanceof DateValue && $to instanceof DateValue) {
            return TimeRecords::prepareCollection('filtered_time_records_in_project_' . $this->active_project->getId() . '_' . $from->toMySQL() . ':' . $to->toMySQL(), $user);
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Add new time record.
     *
     * @return DataObject|int
     * @throws InvalidParamError
     */
    public function add(Request $request, User $user)
    {
        $post = $request->post();

        if (isset($post['value']) && ((float) $post['value'] > 1000000000)) {
            return Response::BAD_REQUEST;
        }

        if (isset($post['task_id']) && $post['task_id']) {
            $track_time_for = DataObjectPool::get(Task::class, $post['task_id']);
        }

        if (array_key_exists('user_id', $post)) {
            /** @var User $assigned_user */
            $assigned_user = Users::findById($post['user_id']);

            if ($assigned_user) {
                if ($assigned_user->getId() != $user->getId()
                    && !TimeRecords::canTrackForOthers($user, $this->active_project)
                ) {
                    return Response::FORBIDDEN;
                }

                $post['user_id'] = $assigned_user->getId();
                $post['user_name'] = $assigned_user->getFullName() ? $assigned_user->getFullName() : null;
                $post['user_email'] = $assigned_user->getEmail();
            } else {
                return Response::BAD_REQUEST;
            }
        } else {
            $post['user_id'] = $user->getId();
            $post['user_name'] = $user->getFullName() ? $user->getFullName() : null;
            $post['user_email'] = $user->getEmail();
        }

        if (empty($track_time_for)) {
            $track_time_for = $this->active_project;
        }

        if ($track_time_for->canTrackTime($user)) {
            $post['parent_type'] = get_class($track_time_for);
            $post['parent_id'] = $track_time_for->getId();

            if (!array_key_exists('billable_status', $post)) {
                $post['billable_status'] = $this->active_project->getDefaultBillableStatus();
            }

            $post['billable_status'] = AngieApplication::getContainer()
                ->get(TrackingBillableStatusResolverInterface::class)
                ->getBillabeStatus($user, $track_time_for, (int) $post['billable_status']);

            $body = $request->getParsedBody();

            $post['source'] = AngieApplication::getContainer()
                ->get(TimeRecordSourceResolverInterface::class)
                ->getSource(
                    $request->getAttribute('authenticated_with'),
                    $body['source'] ?? null
                );

            return TimeRecords::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Show time record data.
     *
     * @return int|TimeRecord
     */
    public function view(Request $request, User $user)
    {
        return $this->active_time_record->isLoaded() && $this->active_time_record->canView($user)
            ? $this->active_time_record
            : Response::NOT_FOUND;
    }

    /**
     * Update a selected time record.
     *
     * @return DataObject|int
     * @throws InvalidParamError
     */
    public function edit(Request $request, User $user)
    {
        if ($this->active_time_record->isLoaded() && $this->active_time_record->canEdit($user)) {
            $put = $request->put();

            if (array_key_exists('user_id', $put)) {
                /** @var User $new_user */
                $new_user = Users::findById($put['user_id']);

                if ($new_user) {
                    if ($new_user->getId() != $this->active_time_record->getUserId()
                        && !TimeRecords::canTrackForOthers($user, $this->active_project)
                    ) {
                        return Response::FORBIDDEN;
                    }

                    $put['user_id'] = $new_user->getId();
                } else {
                    return Response::BAD_REQUEST;
                }
            }

            foreach (['parent_type', 'parent_id', 'task_id', 'project_id'] as $k) {
                if (array_key_exists($k, $put)) {
                    unset($put[$k]);
                }
            }

            /** @var User $assigned_user */
            $assigned_user = !empty($put['user_id']) ? Users::findById($put['user_id']) : null;

            if ($assigned_user) {
                $put['user_name'] = $assigned_user->getFullName() ? $assigned_user->getFullName() : null;
                $put['user_email'] = $assigned_user->getEmail();
            }

            if (array_key_exists('billable_status', $put)) {
                $put['billable_status'] = AngieApplication::getContainer()
                    ->get(TrackingBillableStatusResolverInterface::class)
                    ->getBillabeStatusForTrackingObject(
                        $user,
                        $this->active_time_record,
                        (int) $put['billable_status']
                    );
            }

            return TimeRecords::update($this->active_time_record, $put);
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return TimeRecord|int
     */
    public function move(Request $request, User $user)
    {
        if ($this->active_time_record->isLoaded() && $this->active_time_record->canEdit($user)) {
            $move_to = $this->getMoveToParentFromPut($request->put());

            if ($move_to instanceof ITracking) {
                if ($move_to instanceof Project && !$move_to->getIsTrackingEnabled()) {
                    return Response::NOT_FOUND;
                }

                $this->active_time_record->setParent($move_to, true);
            } else {
                return Response::BAD_REQUEST;
            }

            return $this->active_time_record;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return target task or project from PUT parameters.
     *
     * @param  array             $put
     * @return Project|Task|null
     */
    private function getMoveToParentFromPut($put)
    {
        if (array_key_exists('task_id', $put)) {
            if ($put['task_id']) {
                return DataObjectPool::get('Task', $put['task_id']);
            }

            return DataObjectPool::get('Project', (isset($put['project_id']) ? $put['project_id'] : null));
        }

        if (array_key_exists('project_id', $put)) {
            return DataObjectPool::get('Project', $put['project_id']);
        }

        return null;
    }

    /**
     * Move selected time record to trash.
     *
     * @return bool|DataObject|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_time_record->isLoaded() && $this->active_time_record->canDelete($user) ? TimeRecords::scrap($this->active_time_record) : Response::NOT_FOUND;
    }
}
