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
 * Invoice note templates controller.
 *
 * @package activeCollab.modules.invoicing
 * @subpackage controllers
 */
class InvoiceNoteTemplatesController extends AuthRequiredController
{
    /**
     * Active Invoice note.
     *
     * @var InvoiceNoteTemplate
     */
    protected $active_invoice_note;

    protected function __before(Request $request, $user)
    {
        $before_result = parent::__before($request, $user);

        if ($before_result !== null) {
            return $before_result;
        }

        $this->active_invoice_note = DataObjectPool::get('InvoiceNoteTemplate', $request->getId('invoice_note_template_id'));
        if (empty($this->active_invoice_note)) {
            $this->active_invoice_note = new InvoiceNoteTemplate();
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
        return InvoiceNoteTemplates::prepareCollection(DataManager::ALL, $user);
    }

    /**
     * View invoice note template.
     *
     * @return int|InvoiceNoteTemplate
     */
    public function view(Request $request, User $user)
    {
        return $this->active_invoice_note->isLoaded() && $this->active_invoice_note->canView($user) ? $this->active_invoice_note : Response::NOT_FOUND;
    }

    /**
     * Add Note Page.
     *
     * @return DataObject|int
     */
    public function add(Request $request, User $user)
    {
        return InvoiceNoteTemplates::canAdd($user) ? InvoiceNoteTemplates::create($request->post()) : Response::NOT_FOUND;
    }

    /**
     * Edit Note Page.
     *
     * @return DataObject|int
     */
    public function edit(Request $request, User $user)
    {
        return $this->active_invoice_note->isLoaded() && $this->active_invoice_note->canEdit($user) ? InvoiceNoteTemplates::update($this->active_invoice_note, $request->put()) : Response::NOT_FOUND;
    }

    /**
     * Delete Note Page.
     *
     * @return bool|int
     */
    public function delete(Request $request, User $user)
    {
        return $this->active_invoice_note->isLoaded() && $this->active_invoice_note->canDelete($user) ? InvoiceNoteTemplates::scrap($this->active_invoice_note) : Response::NOT_FOUND;
    }

    /**
     * View default note template.
     *
     * @return InvoiceNoteTemplate|int
     */
    public function view_default()
    {
        if ($note_template = InvoiceNoteTemplates::getDefault()) {
            return $note_template;
        }

        return Response::NOT_FOUND;
    }

    /**
     * Set default note template.
     *
     * @return InvoiceNoteTemplate|int|null
     */
    public function set_default(Request $request)
    {
        /** @var InvoiceNotetemplate $note_template */
        if ($note_template = DataObjectPool::get('InvoiceNoteTemplate', $request->put('invoice_note_template_id'))) {
            return InvoiceNoteTemplates::setDefault($note_template);
        }

        return Response::BAD_REQUEST;
    }

    /**
     * Unset default note template.
     *
     * @return int
     */
    public function unset_default()
    {
        return InvoiceNoteTemplates::setDefault(null);
    }
}
