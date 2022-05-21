<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use ActiveCollab\Module\System\SystemModule;
use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('project', SystemModule::NAME);

class FilesController extends ProjectController
{
    use MoveToProjectControllerAction;

    /**
     * Active file.
     *
     * @var File
     */
    protected $active_file;

    protected function __before(Request $request, $user)
    {
        if ($response = parent::__before($request, $user)) {
            return $response;
        }

        $this->active_file = DataObjectPool::get('File', $request->getId('file_id'));

        if (empty($this->active_file)) {
            $this->active_file = new LocalFile();
            $this->active_file->setProject($this->active_project);
        }

        if ($this->active_file->getProjectId() != $this->active_project->getId()) {
            return Response::NOT_FOUND;
        }
    }

    /**
     * Show filess index page.
     *
     * @return ModelCollection|void
     */
    public function index(Request $request, User $user)
    {
        AccessLogs::logAccess($this->active_project, $user);

        return Files::prepareCollection('files_in_project_' . $this->active_project->getId() . '_page_' . $request->getPage(), $user);
    }

    /**
     * Batch download files.
     *
     * @return array
     */
    public function batch_add(Request $request, User $user)
    {
        if (Files::canAdd($user, $this->active_project)) {
            $result = [];

            $post = $request->post();
            if ($post && is_array($post)) {
                $is_hidden_from_clients = false;

                if (array_key_exists('is_hidden_from_clients', $post)) {
                    $is_hidden_from_clients = (bool) $post['is_hidden_from_clients'];
                    unset($post['is_hidden_from_clients']);
                }

                foreach ($post as $uploaded_file_code) {
                    $result[] = Files::create(['project_id' => $this->active_project->getId(), 'uploaded_file_code' => $uploaded_file_code, 'is_hidden_from_clients' => $is_hidden_from_clients]);
                }
            }

            return $result;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Batch add files.
     */
    public function batch_download()
    {
    }

    /**
     * Show single file.
     *
     * @return int|File
     */
    public function view(Request $request, User $user)
    {
        return $this->active_file->isLoaded() && $this->active_file->canView($user) ? AccessLogs::logAccess($this->active_file, $user) : Response::NOT_FOUND;
    }

    /**
     * Download a file.
     *
     * @return int|File
     */
    public function download(Request $request, User $user)
    {
        return $this->active_file->isLoaded() && $this->active_file->canView($user) ? AccessLogs::logDownload($this->active_file, $user) : Response::NOT_FOUND;
    }

    /**
     * Create a new file.
     *
     * @return File|int
     */
    public function add(Request $request, User $user)
    {
        if (Files::canAdd($user, $this->active_project)) {
            $post = $request->post();

            if ($post && is_array($post)) {
                $post['type'] = 'File';
                $post['project_id'] = $this->active_project->getId();
            }

            return Files::create($post);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Update existing file.
     *
     * @return int
     */
    public function edit(Request $request, User $user)
    {
        if ($this->active_file->isLoaded() && $this->active_file->canEdit($user)) {
            $put = $request->put();

            if ($put && is_foreachable($put)) {
                foreach ($put as $k => $v) {
                    if ($k != 'is_hidden_from_clients') {
                        return Response::BAD_REQUEST;
                    }
                }
            }

            return Files::update($this->active_file, $put);
        }

        return Response::NOT_FOUND;
    }

    /**
     * Move select file to trash.
     *
     * @return DataObject|int|void
     */
    public function delete(Request $request, User $user)
    {
        if ($this->active_file->isLoaded() && $this->active_file->canDelete($user)) {
            $this->active_file->moveToTrash($user);

            return Response::OK;
        }

        return Response::NOT_FOUND;
    }

    /**
     * @return File
     */
    public function &getObjectToBeMoved()
    {
        return $this->active_file;
    }
}
