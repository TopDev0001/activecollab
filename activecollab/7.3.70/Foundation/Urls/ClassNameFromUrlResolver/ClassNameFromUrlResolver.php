<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Urls\ClassNameFromUrlResolver;

use ReflectionClass;
use RuntimeException;

class ClassNameFromUrlResolver implements ClassNameFromUrlResolverInterface
{
    public function getClassNameFromUrl(string $url_parent_type, string $must_implement = null): string
    {
        if (!array_key_exists($url_parent_type, self::SHORT_CLASS_NAMES_MAP)) {
            throw new RuntimeException(
                sprintf(
                    "Parent type '%s' doesn't exist in map of short class names.",
                    $url_parent_type
                )
            );
        }

        $class_name = self::SHORT_CLASS_NAMES_MAP[$url_parent_type];

        if ($must_implement && !(new ReflectionClass($class_name))->implementsInterface($must_implement)) {
            throw new RuntimeException(
                sprintf(
                    "Class '%s' does not implement '%s'.",
                    $class_name,
                    $must_implement
                )
            );
        }

        return $class_name;
    }
}
