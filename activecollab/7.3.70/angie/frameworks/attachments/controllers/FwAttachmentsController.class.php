<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

/**
 * Framework level attachments controller.
 *
 * @package angie.frameworks.attachments
 * @subpackage controllers
 */
abstract class FwAttachmentsController extends AuthRequiredController
{
    /**
     * Active attachment.
     *
     * @var Attachment
     */
    protected $active_attachment;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_attachment = DataObjectPool::get('Attachment', $request->getId('attachment_id'));

        if (empty($this->active_attachment)) {
            $this->active_attachment = new LocalAttachment();
        }
    }

    /**
     * @return int
     */
    public function index()
    {
        return Response::NOT_FOUND;
    }

    /**
     * @return Attachment|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_attachment->isLoaded() && $this->active_attachment->canView($user) ? $this->active_attachment : Response::NOT_FOUND;
    }

    /**
     * Download a file.
     *
     * @return Attachment|int
     */
    public function download(Request $request, User $user)
    {
        return $this->active_attachment->isLoaded() && $this->active_attachment->canView($user) ? $this->active_attachment->prepareForDownload() : Response::NOT_FOUND;
    }

    /**
     * @return int
     */
    public function edit()
    {
        return Response::NOT_FOUND;
    }

    /**
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_attachment->isLoaded() && $this->active_attachment->canDelete($user) ? Attachments::scrap($this->active_attachment, true) : Response::NOT_FOUND;
    }
}
