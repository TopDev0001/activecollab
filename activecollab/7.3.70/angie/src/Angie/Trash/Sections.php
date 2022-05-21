<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Trash;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidParamError;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Trash section.
 *
 * @package Angie\Trash
 */
final class Sections implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable
{
    public const FIRST_WAVE = 0;
    public const SECOND_WAVE = 1;
    public const THIRD_WAVE = 2;
    public const FOURTH_WAVE = 3;

    /**
     * @var array
     */
    private $data = [];
    private $empty_in_waves = [0 => [], 1 => [], 2 => [], 3 => []];

    /**
     * Register an object.
     *
     * Trash::addObject('Project', [
     *   1 => 'ActiveCollab Legacy',
     *   2 => 'ActiveCollab Feather',
     * ]);
     *
     * @param  string             $type
     * @param  array              $id_name_map
     * @param  int                $empty_in_wave
     * @throws \InvalidParamError
     */
    public function registerTrashedObjects($type, $id_name_map, $empty_in_wave = self::FIRST_WAVE)
    {
        if ($id_name_map && is_foreachable($id_name_map)) {
            if (isset($this->data[$type]) && is_foreachable($this->data[$type])) {
                throw new InvalidParamError('type', $type, 'Type already registered');
            }

            $this->data[$type] = $id_name_map;
        }

        if (empty($empty_in_wave)) {
            $empty_in_wave = self::FIRST_WAVE;
        }

        if (empty($this->empty_in_waves[$empty_in_wave])) {
            $this->empty_in_waves[$empty_in_wave] = [];
        }

        $this->empty_in_waves[$empty_in_wave][] = $type;
    }

    /**
     * Return empty in waves.
     *
     * @return array
     */
    public function getEmptyInWaves()
    {
        return $this->empty_in_waves;
    }

    /**
     * Check if $offset exists.
     *
     * @param  string $offset
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Return value at $offset.
     *
     * @param  string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return empty($this->data[$offset]) && !array_key_exists($offset, $this->data) ? null : $this->data[$offset];
    }

    /**
     * Set value at $offset.
     *
     * @param string $offset
     * @param mixed  $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * Unset value at $offset.
     *
     * @param string $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Number of elements.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->data);
    }

    /**
     * Returns an iterator for for this object, for use with foreach.
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Return serialized data.
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
