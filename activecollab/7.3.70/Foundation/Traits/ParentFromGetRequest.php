<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Traits;

use ActiveCollab\Foundation\Urls\ClassNameFromUrlResolver\ClassNameFromUrlResolverInterface;
use Angie\Http\Request;
use AngieApplication;
use DataObject;
use DataObjectPool;
use Exception;

trait ParentFromGetRequest
{
    protected function getParentFromRequest(Request $request, string $should_implement = null): ?DataObject
    {
        $parent_type = $request->get('parent_type', '');
        $parent_id = $request->get('parent_id');

        try {
            return DataObjectPool::get(
                AngieApplication::getContainer()
                    ->get(ClassNameFromUrlResolverInterface::class)
                    ->getClassNameFromUrl($parent_type, $should_implement),
                $parent_id
            );
        } catch (Exception $e) {
            AngieApplication::log()->info(
                "Parent object from request wasn't find.",
                [
                    'parent_type' => $parent_type,
                    'parent_id' => $parent_id,
                ]
            );

            return null;
        }
    }
}
