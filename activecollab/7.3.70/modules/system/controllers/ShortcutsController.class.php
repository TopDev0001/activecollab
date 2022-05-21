<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Module\System\SystemModule;
use ActiveCollab\Module\System\Utils\ReorderService\OrderDataManager;
use ActiveCollab\Module\System\Utils\ReorderService\ReorderServiceInterface;
use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\StatusResponse\StatusResponse;

AngieApplication::useController('auth_required', SystemModule::NAME);

class ShortcutsController extends AuthRequiredController
{
    protected ?Shortcut $active_shortcut;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        if ($shortcut_id = $request->getId('shortcut_id')) {
            if ($this->active_shortcut = DataObjectPool::get(Shortcut::class, $shortcut_id)) {
                if (!$this->active_shortcut->canView($user)) {
                    return Response::FORBIDDEN;
                }
            } else {
                return Response::NOT_FOUND;
            }
        } else {
            $this->active_shortcut = new Shortcut();
        }

        return null;
    }

    public function index(Request $request, User $user)
    {
        return Shortcuts::prepareRelativeCursorCollection('user_shortcuts', $user);
    }

    public function create(Request $request, User $user)
    {
        $name = (string) $request->post('name');
        $url = (string) $request->post('url');
        $icon = (string) $request->post('icon');

        if (empty($name) || empty($url) || empty($icon)) {
            return Response::BAD_REQUEST;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return Response::BAD_REQUEST;
        }

        if (!in_array($icon, Shortcut::ICONS)) {
            return Response::BAD_REQUEST;
        }

        try {
            return Shortcuts::create([
                'name' => $name,
                'url' => $url,
                'icon' => $icon,
                'created_by_id' => $user->getId(),
                'created_by_name' => $user->getName(),
                'created_by_email' => $user->getEmail(),
            ]);
        } catch (Exception $e) {
            AngieApplication::log()->error($e->getMessage());

            return Response::NOT_ACCEPTABLE;
        }
    }

    public function view(Request $request, User $user)
    {
        return $this->active_shortcut;
    }

    public function delete(Request $request, User $user)
    {
        if (!$this->active_shortcut->canDelete($user)) {
            return Response::FORBIDDEN;
        }

        $this->active_shortcut->delete();

        return Response::OK;
    }

    public function batch_delete(Request $request, User $user)
    {
        $shortcut_ids = $request->put('ids');

        if (!empty($shortcut_ids)) {
            return Shortcuts::batchScrapBy($shortcut_ids, $user);
        }

        return Response::BAD_REQUEST;
    }

    public function edit(Request $request, User $user)
    {
        if (!$this->active_shortcut->canEdit($user)) {
            return Response::FORBIDDEN;
        }

        $name = (string) $request->put('name');
        $url = (string) $request->put('url');
        $icon = (string) $request->put('icon');

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return Response::BAD_REQUEST;
        }

        if (!in_array($icon, Shortcut::ICONS)) {
            return Response::BAD_REQUEST;
        }

        try {
            return Shortcuts::update(
                $this->active_shortcut,
                [
                    'name' => $name,
                    'url' => $url,
                    'icon' => $icon,
                ]
            );
        } catch (LogicException $e) {
            return new StatusResponse(
                Response::BAD_REQUEST,
                '',
                ['message' => $e->getMessage()]
            );
        }
    }

    public function reorder(Request $request, User $user)
    {
        $changes = $request->put('changes');

        if (empty($changes) || !is_array($changes)) {
            return Response::BAD_REQUEST;
        }

        foreach ($changes as $change) {
            if (!array_key_exists('source', $change) || !array_key_exists('target', $change)) {
                return Response::BAD_REQUEST;
            }
        }

        try {
            return AngieApplication::getContainer()
                ->get(ReorderServiceInterface::class)
                ->setDataManager(new OrderDataManager(Shortcut::class))
                ->reorder($changes, $user);
        } catch (Exception $e) {
            AngieApplication::log()->error(
                'Failed to reorder shortcuts', [
                    'exception_message' => $e->getMessage(),
                    'changes' => $changes,
            ]);

            return new StatusResponse(
                Response::OPERATION_FAILED,
                '',
                ['message' => lang('Something went wrong. Please try again or contact customer support.')]
            );
        }
    }
}
