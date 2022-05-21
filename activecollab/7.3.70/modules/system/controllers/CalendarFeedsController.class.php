<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;
use Angie\Http\Response\FileDownload\FileDownload;
use Angie\Http\Response\FileDownload\FileDownloadInterface;

AngieApplication::useController('auth_not_required', CalendarsFramework::INJECT_INTO);

/**
 * Calendar feeds controller.
 *
 * @package ActiveCollab.modules.system
 * @subpackage controllers
 */
class CalendarFeedsController extends AuthNotRequiredController
{
    /**
     * User authenticated using feed token.
     *
     * @var User
     */
    protected $active_user;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_user = Users::findByFeedToken($request->get('feed_token'));

        if (empty($this->active_user) || !$this->active_user->isActive()) {
            return Response::NOT_FOUND;
        }

        return null;
    }

    /**
     * Serve global calendar feed.
     */
    public function index()
    {
        return Response::NOT_FOUND;
    }

    /**
     * Serve calendar feed for the given calendar.
     *
     * @return FileDownload|int
     */
    public function project(Request $request)
    {
        /** @var Project $project */
        $project = DataObjectPool::get('Project', $request->get('project_id'));

        if ($project instanceof Project && $project->canView($this->active_user)) {
            return new FileDownload($project->exportCalendarToFile($this->active_user), 'text/calendar', $project->getName() . '.ics', $this->getDownloadOrInlineFromRequest($request));
        }

        return Response::NOT_FOUND;
    }

    /**
     * Serve calendar feed for the given calendar.
     *
     * @return FileDownload|int
     */
    public function calendar(Request $request)
    {
        /** @var Calendar $calendar */
        $calendar = DataObjectPool::get('Calendar', $request->get('calendar_id'));

        if ($calendar instanceof Calendar && $calendar->canView($this->active_user)) {
            return new FileDownload($calendar->exportCalendarToFile($this->active_user), 'text/calendar', $calendar->getName() . '.ics', $this->getDownloadOrInlineFromRequest($request));
        }

        return Response::NOT_FOUND;
    }

    /**
     * Return file download disposition based on request object.
     *
     * @return string
     */
    private function getDownloadOrInlineFromRequest(Request $request)
    {
        return $request->get('download') ? FileDownloadInterface::DOWNLOAD_ATTACHMENT : FileDownloadInterface::DOWNLOAD_INLINE;
    }
}
