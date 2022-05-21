<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\Tracking\Services\StopwatchServiceInterface;
use ActiveCollab\Module\Tracking\Utils\StopwatchManagerInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', SystemModule::NAME);

class StopwatchController extends AuthRequiredController
{
    public function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        if ($user->isClient()) {
            return Response::FORBIDDEN;
        }

        return null;
    }

    public function index(Request $request, User $user)
    {
        try {
            return AngieApplication::getContainer()
                ->get(StopwatchManagerInterface::class)
                ->getStopwatchesCollectionForUser($user);
        } catch (Exception $exception) {
            $this->logErrorException($exception);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }

    public function start(Request $request, User $user)
    {
        $post = $request->post();

        $parent_type = (string) $post['parent_type'];
        $parent_id = (int) $post['parent_id'];
        $elapsed = isset($post['elapsed']) ? (int) $post['elapsed'] : 0;

        if (!$this->denyAccessUnlessIsGranted($user, $parent_type, $parent_id)) {
            return Response::FORBIDDEN;
        }

        try {
            return AngieApplication::getContainer()
                ->get(StopwatchServiceInterface::class)
                ->start(
                    $user,
                    $parent_type,
                    $parent_id,
                    $elapsed
                );
        } catch (Exception $exception) {
            $this->logErrorException($exception);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }

    public function resume(Request $request, User $user)
    {
        $id = $request->get('id');
        $stopwatch = AngieApplication::getContainer()
            ->get(StopwatchManagerInterface::class)
            ->findOneByUserAndId($user->getId(), (int) $id);

        if (!$stopwatch) {
            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('Stopwatch does not exist.')]
            );
        }

        try {
            return AngieApplication::getContainer()
                ->get(StopwatchServiceInterface::class)
                ->resume($stopwatch);
        } catch (Exception $exception) {
            $this->logErrorException($exception);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }

    public function pause(Request $request, User $user)
    {
        $id = $request->get('id');
        $stopwatch = AngieApplication::getContainer()
            ->get(StopwatchManagerInterface::class)
            ->findOneByUserAndId($user->getId(), (int) $id);

        if (!$stopwatch) {
            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('Stopwatch does not exist.')]
            );
        }

        try {
            return AngieApplication::getContainer()
                ->get(StopwatchServiceInterface::class)
                ->pause($stopwatch);
        } catch (Exception $exception) {
            $this->logErrorException($exception);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }

    public function edit(Request $request, User $user)
    {
        $id = $request->get('id');
        $attributes = $request->put();
        $stopwatch = AngieApplication::getContainer()
            ->get(StopwatchManagerInterface::class)
            ->findOneByUserAndId($user->getId(), (int) $id);

        if (!$stopwatch) {
            return new StatusResponse(
                Response::NOT_FOUND,
                '',
                ['message' => lang('Stopwatch does not exist.')]
            );
        }

        try {
            return AngieApplication::getContainer()
                ->get(StopwatchServiceInterface::class)
                ->edit($stopwatch, $attributes);
        } catch (Exception $exception) {
            $this->logErrorException($exception);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }

    public function delete(Request $request, User $user)
    {
        $stopwatch = AngieApplication::getContainer()
            ->get(StopwatchManagerInterface::class)
            ->findOneByUserAndId($user->getId(), (int) $request->get('id'));

        if (!$stopwatch) {
            return new StatusResponse(
                204,
                '',
                ['message' => lang('Stopwatch does not exist.')]
            );
        }

        try {
            return AngieApplication::getContainer()
                ->get(StopwatchServiceInterface::class)
                ->delete($stopwatch);
        } catch (Exception $exception) {
            $this->logErrorException($exception);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }

    public function offset(Request $request) {
        $dateTime = DateTimeValue::now();
        $client_moment = $request->post('timestamp') ?? $dateTime->getTimestamp();
        $offset = -1 * ($dateTime->getTimestamp() - $client_moment);

        return [
            'additional_object_data' => [
                'stopwatch_offset' => $offset,
            ],
        ];
    }

    private function denyAccessUnlessIsGranted(User $user, string $parent_type, int $parent_id)
    {
        if ($parent_type === Project::class) {
            /** @var Project $project */
            $project = Projects::findOneBy([
                'id' => $parent_id,
                'is_trashed' => false,
            ]);

            return $project && $project->canView($user);
        }

        if ($parent_type === Task::class) {
            /** @var Task $task */
            $task = Tasks::findOneBy([
                'id' => $parent_id,
                'is_trashed' => false,
            ]);

            return $task && $task->canView($user);
        }

        return false;
    }

    private function logErrorException(Exception $exception)
    {
        AngieApplication::log()->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
