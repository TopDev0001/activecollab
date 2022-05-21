<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

class InvoiceNoteTemplate extends BaseInvoiceNoteTemplate
{
    public function jsonSerialize(): array
    {
        $result = parent::jsonSerialize();

        $result['name'] = $this->getName();
        $result['content'] = $this->getContent();
        $result['is_default'] = $this->getIsDefault();

        return $result;
    }

    public function getRoutingContext(): string
    {
        return 'invoice_note_template';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'invoice_note_template_id' => $this->getId(),
        ];
    }

    public function canView(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function canEdit(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function canDelete(User $user): bool
    {
        return $user->isFinancialManager();
    }

    public function validate(ValidationErrors & $errors)
    {
        $this->validatePresenceOf('name') or $errors->fieldValueIsRequired('name');
        $this->validateUniquenessOf('name') or $errors->fieldValueNeedsToBeUnique('name');
        $this->validatePresenceOf('content') or $errors->fieldValueIsRequired('content');

        return parent::validate($errors);
    }
}
