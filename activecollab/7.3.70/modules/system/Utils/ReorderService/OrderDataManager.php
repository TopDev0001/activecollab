<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\ReorderService;

use ActiveCollab\User\UserInterface;
use Angie\Inflector;
use DB;
use RuntimeException;

class OrderDataManager implements OrderableDataManagerInterface
{
    private string $model_name;
    private $manager;

    public function __construct(string $model_class)
    {
        $this->manager = Inflector::pluralize($model_class);
        $this->model_name = $this->manager::getModelName(true);
        $this->checkIsOrderable();
    }

    public function getItemsByPosition(?UserInterface $user = null): array
    {
        $created_by = '';
        if ($user instanceof UserInterface) {
            $created_by = " WHERE created_by_id = {$user->getId()}";
        }

        $shortcuts = DB::execute(
            "SELECT id, position FROM `{$this->model_name}`{$created_by} ORDER by position ASC",
        );

        return $shortcuts ? $shortcuts->toArray() : [];
    }

    public function updatePositions(array $to_update): void
    {
        $when_then_cases = '';
        $affected_items = [];
        foreach ($to_update as $change) {
            $when_then_cases .= "WHEN {$change['id']} THEN {$change['new_position']} ";
            $affected_items[] = $change['id'];
        }

        if (!empty($affected_items)) {
            DB::execute(
                "UPDATE `{$this->model_name}` 
                    SET `updated_on` = UTC_TIMESTAMP(), 
                        `position` = (CASE `id` $when_then_cases END) 
                    WHERE `id` IN (?)",
                $affected_items
            );

            $this->manager::clearCacheFor($affected_items);
        }
    }

    private function checkIsOrderable(): void
    {
        if (!in_array(OrderableDataManagerInterface::POSITION_FILED, $this->manager::getFields())) {
            throw new RuntimeException("Model '{$this->model_name}' not support order.");
        }
    }
}
