<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

$this->mapResource(
    'attachments',
    [
        'module' => AttachmentsFramework::INJECT_INTO,
    ],
    function (array $collection, array $single) {
        $this->map(
            "$single[name]_download",
            "$single[path]/download",
            [
                'controller' => $single['controller'],
                'action' => [
                    'GET' => 'download',
                ],
                'module' => $single['module'],
            ],
            $single['requirements']
        );
        $this->map(
            "$collection[name]_batch_download",
            "$collection[path]/:parent_type/:parent_id/download",
            [
                'controller' => 'attachments_archive',
                'action' => [
                    'POST' => 'prepare',
                ],
                'module' => $collection['module'],
            ],
            $collection['requirements']
        );
    }
);
