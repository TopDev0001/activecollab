<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

$this->mapResource(
    'labels',
    [
        'module' => LabelsFramework::INJECT_INTO,
    ],
    function ($collection, $single) {
        $this->map(
            "$collection[name]_reorder",
            "$collection[path]/reorder",
            [
                'action' => [
                    'PUT' => 'reorder',
                ],
                'controller' => 'labels',
                'module' => LabelsFramework::INJECT_INTO,
            ], $collection['requirements']
        );
        $this->map(
            "$single[name]_set_as_default",
            "$single[path]/set-as-default",
            [
                'action' => [
                    'PUT' => 'set_as_default',
                ],
                'controller' => 'labels',
                'module' => LabelsFramework::INJECT_INTO,
            ],
            $single['requirements']
        );
    }
);
