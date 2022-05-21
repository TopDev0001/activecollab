<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Model\Conversation;

use IMembers;
use IUser;
use ValidationErrors;

class ParentObjectConversation extends SmartConversation
{
    public function getMemberIds(bool $use_cache = true): array
    {
        $parent = $this->getParent();

        return $parent && $parent instanceof IMembers ? $parent->getMemberIds($use_cache) : [];
    }

    public function validate(ValidationErrors &$errors)
    {
        if ($this->getFieldValue('type') === ParentObjectConversation::class &&
            (!$this->validatePresenceOf('parent_type') || !$this->validatePresenceOf('parent_id'))
        ) {
            $errors->addError('Parent type fields are required.', 'parent_type');
        }

        if ($this->getFieldValue('type') === ParentObjectConversation::class &&
            !($this->getParent() instanceof IMembers)
        ) {
            $errors->addError('Parent object must be instance of IMembers.', 'parent_type');
        }

        parent::validate($errors);
    }

    public function getExtendedTimestampValue(): string
    {
        $parent = $this->getParent();

        return $parent ? $parent->getUpdatedOn()->toMySQL() : '';
    }

    public function getDisplayName(IUser $user): string
    {
        return $this->getName();
    }
}
