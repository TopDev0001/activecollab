<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

abstract class FwAttachment extends BaseAttachment
{
    public function isParentOptional(): bool
    {
        return false;
    }

    public function getRoutingContext(): string
    {
        return 'attachment';
    }

    public function getRoutingContextParams(): array
    {
        return [
            'attachment_id' => $this->getId(),
        ];
    }

    public function getBaseTypeName(bool $singular = true): string
    {
        return $singular ? 'attachment' : 'attachments';
    }

    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['disposition' => $this->getDisposition()]);
    }

    /**
     * Describe single.
     */
    public function describeSingleForFeather(array & $result)
    {
        parent::describeSingleForFeather($result);

        $result['parent'] = $this->getParent();

        if ($result['parent'] instanceof Comment) {
            $result['parent'] = $result['parent']->getParent();
        }
    }

    // ---------------------------------------------------
    //  System
    // ---------------------------------------------------

    /**
     * Validate before save.
     */
    public function validate(ValidationErrors & $errors)
    {
        $this->validatePresenceOf('name') or $errors->fieldValueIsRequired('name');
        $this->validatePresenceOf('mime_type') or $errors->fieldValueIsRequired('mime_type');

        parent::validate($errors);
    }

    /**
     * Delete attachment.
     *
     * @param bool $bulk
     */
    public function delete($bulk = false)
    {
        if (!$bulk) {
            $parent = $this->getParent();

            if ($parent instanceof IAttachments) {
                $search_item_parent = $parent->updateSearchItemOnAttachmentsChange();
            }
        }

        parent::delete($bulk);

        if (!empty($search_item_parent)) {
            AngieApplication::search()->update($search_item_parent);
        }
    }
}
