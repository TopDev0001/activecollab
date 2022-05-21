<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class LabelsController extends AuthRequiredController
{
    /**
     * Parent object instance.
     *
     * @var ILabels|ApplicationObject
     */
    protected $active_label;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_label = DataObjectPool::get(Label::class, $request->getId('label_id'));

        return null;
    }

    /**
     * List object labels.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return Labels::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * Reorder labels.
     *
     * @return int
     */
    public function reorder(Request $request, User $user)
    {
        if (Labels::canReorder($user)) {
            Labels::reorder($request->put());

            return $request->put();
        }

        return Response::NOT_FOUND;
    }

    /**
     * View a signle label.
     *
     * @return Label|int
     */
    public function view(Request $request, User $user)
    {
        return $this->active_label instanceof Label && $this->active_label->isLoaded() && $this->active_label->canView($user)
            ? $this->active_label
            : Response::NOT_FOUND;
    }

    /**
     * Define a new label.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return Labels::canAdd($user) ? Labels::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * Update the selected label.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_label instanceof Label && $this->active_label->isLoaded() && $this->active_label->canEdit($user)
            ? Labels::update($this->active_label, $request->put())
            : Response::NOT_FOUND;
    }

    /**
     * Delete the selected label.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_label instanceof Label && $this->active_label->isLoaded() && $this->active_label->canDelete($user)
            ? Labels::scrap($this->active_label)
            : Response::NOT_FOUND;
    }

    /**
     * Set label as default.
     *
     * @return Label|int
     */
    public function set_as_default(Request $request, User $user)
    {
        if ($this->active_label instanceof Label && $this->active_label->isLoaded() && $this->active_label->canEdit($user)) {
            if ($this->active_label->getIsDefault()) {
                Labels::unsetDefault($this->active_label);
            } else {
                Labels::setDefault($this->active_label);
            }

            return $this->active_label;
        }

        return Response::NOT_FOUND;
    }

    public function project_labels(Request $request, User $user)
    {
        return Labels::prepareCollection('project_labels', $user);
    }

    public function task_labels(Request $request, User $user)
    {
        return Labels::prepareCollection('task_labels', $user);
    }
}
