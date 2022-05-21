<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\Http\Request;
use Angie\Http\Response;

AngieApplication::useController('auth_required', EnvironmentFramework::INJECT_INTO);

class InvoiceItemTemplatesController extends AuthRequiredController
{
    /**
     * Currently active predefined invoice item.
     *
     * @var InvoiceItemTemplate
     */
    protected $active_item_template;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_item_template = DataObjectPool::get('InvoiceItemTemplate', $request->getId('invoice_item_template_id'));
        if (empty($this->active_item_template)) {
            $this->active_item_template = new InvoiceItemTemplate();
        }

        return null;
    }

    /**
     * Predefined items main page.
     *
     * @return ModelCollection
     */
    public function index(Request $request, User $user)
    {
        return InvoiceItemTemplates::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * Add invoice item template.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return InvoiceItemTemplates::canAdd($user) ? InvoiceItemTemplates::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * View invoice item template.
     *
     * @return int|InvoiceItemTemplate
     */
    public function view(Request $request, User $user)
    {
        return $this->active_item_template->isLoaded() && $this->active_item_template->canView($user) ? $this->active_item_template : Response::NOT_FOUND;
    }

    /**
     * Edit Note Page.
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_item_template->isLoaded() && $this->active_item_template->canEdit($user) ? InvoiceItemTemplates::update($this->active_item_template, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Delete Invoice Item Template.
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_item_template->isLoaded() && $this->active_item_template->canDelete($user) ? InvoiceItemTemplates::scrap($this->active_item_template) : Response::NOT_FOUND;
    }
}
