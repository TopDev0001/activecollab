<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\AdapterFactory;

use Angie\Search\Adapter\AdapterInterface;

/**
 * @package Angie\Search\AdapterFactory
 */
interface SearchAdapterFactoryInterface
{
    /**
     * @param  string                $class_name
     * @param  bool                  $is_on_demand
     * @return AdapterInterface|null
     */
    public function produce($class_name, $is_on_demand = false);

    /**
     * @return string
     */
    public function getIndexName();

    /**
     * @return string
     */
    public function getDocumentType();

    /**
     * @return int
     */
    public function getTenantId();
}
