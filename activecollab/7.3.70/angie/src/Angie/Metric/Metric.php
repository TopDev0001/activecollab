<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Metric;

use Angie\Inflector;
use Angie\Metric\Result\ResultInterface;
use DateValue;
use LogicException;

abstract class Metric implements MetricInterface
{
    public function getName(): string
    {
        $bits = explode('\\', get_class($this));
        $class_name = array_pop($bits);

        if (str_ends_with($class_name, $this->getClassNameSufix())) {
            return Inflector::underscore(
                substr(
                    $class_name,
                    0,
                    strlen($class_name) - strlen($this->getClassNameSufix())
                )
            );
        } else {
            return Inflector::underscore($class_name);
        }
    }
    protected function getClassNameSufix(): string
    {
        return '';
    }

    abstract protected function getResultClassName(): string;

    protected function produceResult($result, DateValue $date): ResultInterface
    {
        $result_class_name = $this->getResultClassName();

        if (empty($result_class_name)) {
            throw new LogicException('Metric needs to return a proper result class name');
        }

        return new $result_class_name($this->getName(), $result, $date);
    }

    /**
     * @return string[]
     */
    protected function dateToRange(DateValue $date): array
    {
        $day = $date->format('Y-m-d');

        return [
            "{$day} 00:00:00",
            "{$day} 23:59:59",
        ];
    }
}
