<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Wrappers\Cache\DefaultCacheLifetimeResolver;

use ActiveCollab\Foundation\Wrappers\Cache\CacheInterface;
use Angie\Utils\ConstantResolverInterface;

class DefaultCacheLifetimeResolver implements DefaultCacheLifetimeResolverInterface
{
    private int $default_cache_lifetime;

    public function __construct(ConstantResolverInterface $constant_resolver)
    {
        $lifetime_from_constant = $constant_resolver->getValueForConstant('CACHE_LIFETIME');

        $this->default_cache_lifetime = is_int($lifetime_from_constant) && $lifetime_from_constant > 0
            ? $lifetime_from_constant
            : CacheInterface::DEFAULT_LIFETIME;
    }

    public function getDefaultCacheLifetime(): int
    {
        return $this->default_cache_lifetime;
    }
}
