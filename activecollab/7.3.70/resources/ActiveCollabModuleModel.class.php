<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

class ActiveCollabModuleModel extends AngieModuleModel
{
    protected function addCompany(string $name, array $additional = null): int
    {
        $properties = ['name' => $name];

        if (is_array($additional)) {
            $properties = array_merge($properties, $additional);
        }

        $properties['created_on'] = date(DATETIME_MYSQL);
        if (!isset($properties['created_by_id'])) {
            $properties['created_by_id'] = 1;
        }

        $properties['updated_on'] = date(DATETIME_MYSQL);
        $properties['updated_by_id'] = $properties['created_by_id'];

        return $this->createObject('companies', $properties);
    }

    protected function addUser(
        string $email,
        int $company_id,
        array $additional = null
    ): int
    {
        $properties = array_merge(
            $additional ?? [],
            [
                'company_id' => $company_id,
                'email' => $email,
                'password_hashed_with' => 'php',
                'created_on' => date(DATETIME_MYSQL),
                'updated_on' => date(DATETIME_MYSQL),
            ]
        );

        $properties['password'] = password_hash(
            APPLICATION_UNIQUE_KEY . ($properties['password'] ?? 'test'),
            PASSWORD_DEFAULT
        );

        if (empty($properties['created_by_id'])) {
            $properties['created_by_id'] = 1;
        }

        return $this->createObject('users', $properties);
    }
}
