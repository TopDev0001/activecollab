<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Metric;

use Angie\Inflector;
use Angie\Metric\Collection;
use Angie\Metric\Result\ResultInterface;
use Client;
use DateValue;
use DBConnection;
use Member;
use Owner;

final class UsersCollection extends Collection
{
    private $connection;

    public function __construct(DBConnection $connection)
    {
        $this->connection = $connection;
    }

    public function getValueFor(DateValue $date): ResultInterface
    {
        $until_timestamp = $this->dateToRange($date)[1];

        $total = 0;
        $total_active = 0;
        $total_archived = 0;

        $active_by_role = [
            $this->normalizeRoleClassName(Owner::class) => 0,
            $this->normalizeRoleClassName(Member::class) => 0,
            $this->normalizeRoleClassName(Client::class) => 0,
        ];

        $archived_by_role = [
            $this->normalizeRoleClassName(Owner::class) => 0,
            $this->normalizeRoleClassName(Member::class) => 0,
            $this->normalizeRoleClassName(Client::class) => 0,
        ];

        $active_project_managers = 0;
        $active_financial_managers = 0;
        $active_client_plus = 0;
        $with_company = 0;

        if ($rows = $this->connection->execute(
            "SELECT
                    COUNT(`id`) as 'row_count',
                    `type`,
                    (`company_id` > ?) AS 'with_company',
                    (`archived_on` IS NOT NULL AND `archived_on` <= ?) AS 'is_archived',
                    `raw_additional_properties`
                FROM `users`
                WHERE (`trashed_on` IS NULL OR `trashed_on` > ?) AND `created_on` <= ?
                GROUP BY `type`, `with_company`, `archived_on`, `raw_additional_properties`",
            [
                0,
                $until_timestamp,
                $until_timestamp,
                $until_timestamp,
            ]
        )) {
            foreach ($rows as $row) {
                $total += $row['row_count'];

                if ($row['is_archived']) {
                    $total_archived += $row['row_count'];
                } else {
                    $total_active += $row['row_count'];
                }

                if ($row['with_company']) {
                    $with_company += $row['row_count'];
                }

                $this->incUserStats(
                    $row['row_count'],
                    $row['type'],
                    $row['is_archived'],
                    $active_by_role,
                    $archived_by_role
                );

                $this->incRoleVariationCounters(
                    $row['row_count'],
                    $row['type'],
                    $row['is_archived'],
                    $this->getCustomPermissionsFromAdditionalProperties(
                        $row['raw_additional_properties']
                    ),
                    $active_project_managers,
                    $active_financial_managers,
                    $active_client_plus
                );
            }
        }

        return $this->produceResult(
            [
                'total' => $total,
                'total_active' => $total_active,
                'total_archived' => $total_archived,
                'total_billable' => $this->getTotalBillable($active_by_role, $active_client_plus),
                'active_by_role' => $active_by_role,
                'active_project_managers' => $active_project_managers,
                'active_financial_managers' => $active_financial_managers,
                'active_client_plus' => $active_client_plus,
                'archived_by_role' => $archived_by_role,
                'with_company' => $with_company,
            ],
            $date
        );
    }

    private function incUserStats(
        $number_of_users,
        $user_role,
        $is_archived,
        array &$active_by_role,
        array &$archived_by_role
    )
    {
        $normalized_user_role = $this->normalizeRoleClassName($user_role);

        if ($is_archived) {
            $archived_by_role[$normalized_user_role] += $number_of_users;
        } else {
            $active_by_role[$normalized_user_role] += $number_of_users;
        }
    }

    private function incRoleVariationCounters(
        $number_of_users,
        $user_role,
        $is_archived,
        $custom_permissions,
        &$active_project_managers,
        &$active_financial_managers,
        &$active_client_plus
    )
    {
        if ($is_archived) {
            return;
        }

        if ($user_role === Member::class) {
            $this->incMemberRoleVariationStats(
                $number_of_users,
                $custom_permissions,
                $active_project_managers,
                $active_financial_managers
            );
        } elseif ($user_role === Client::class) {
            $this->incClientRoleVariationStats(
                $number_of_users,
                $custom_permissions,
                $active_client_plus
            );
        }
    }

    private function incMemberRoleVariationStats(
        $number_of_users,
        $custom_permissions,
        &$active_project_managers,
        &$active_financial_managers
    )
    {
        if (in_array(Member::CAN_MANAGE_PROJECTS, $custom_permissions)) {
            $active_project_managers += $number_of_users;
        }

        if (in_array(Member::CAN_MANAGE_FINANCES, $custom_permissions)) {
            $active_financial_managers += $number_of_users;
        }
    }

    private function incClientRoleVariationStats(
        $number_of_users,
        $custom_permissions,
        &$active_client_plus
    )
    {
        if (in_array(Client::CAN_MANAGE_TASKS, $custom_permissions)) {
            $active_client_plus += $number_of_users;
        }
    }

    private function getCustomPermissionsFromAdditionalProperties($additional_properties)
    {
        if (!empty($additional_properties)) {
            $values = unserialize($additional_properties);

            if (!empty($values['custom_permissions']) && is_array($values['custom_permissions'])) {
                return $values['custom_permissions'];
            }
        }

        return [];
    }

    private $normalized_role_class_names = [];

    private function normalizeRoleClassName($role_class_name)
    {
        if (empty($this->normalized_role_class_names[$role_class_name])) {
            $this->normalized_role_class_names[$role_class_name] = Inflector::underscore($role_class_name);
        }

        return $this->normalized_role_class_names[$role_class_name];
    }

    private function getTotalBillable(array $active_by_role, int $active_client_plus): int
    {
        return $active_by_role[$this->normalizeRoleClassName(Owner::class)] +
            $active_by_role[$this->normalizeRoleClassName(Member::class)] +
            $active_client_plus;
    }
}
