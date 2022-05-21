<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * TestDataObject class.
 *
 * @package angie.tests
 */
abstract class FwTestDataObject extends BaseTestDataObject
{
    /**
     * Validate before save.
     */
    public function validate(ValidationErrors &$errors)
    {
        if ($this->validatePresenceOf('name')) {
            $this->validateUniquenessOf('name') or $errors->addError('Name need to be unique', 'name');
        } else {
            $errors->addError('Name value is required', 'name');
        }

        parent::validate($errors);
    }
}
