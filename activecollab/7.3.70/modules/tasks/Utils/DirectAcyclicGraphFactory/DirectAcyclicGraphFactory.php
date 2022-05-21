<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\Tasks\Utils\DirectAcyclicGraphFactory;

class DirectAcyclicGraphFactory implements DirectAcyclicGraphFactoryInterface
{
    public function createStructure(array $parent_child_dependencies, bool $from_child_to_parent = false): array
    {
        $result = [];

        if (empty($parent_child_dependencies)) {
            return $result;
        }

        $key = $from_child_to_parent ? 'child_id' : 'parent_id';
        $value = $from_child_to_parent ? 'parent_id' : 'child_id';

        foreach ($parent_child_dependencies as $dependency) {
            if (!array_key_exists($dependency[$key], $result)) {
                $result[$dependency[$key]] = [$dependency[$value]];
            } elseif (!in_array($dependency[$value], $result[$dependency[$key]])) {
                array_push($result[$dependency[$key]], $dependency[$value]);
            }
        }

        return $result;
    }
}
