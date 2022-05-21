<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\TestCase;

use DateValue;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected ?int $current_timestamp = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->current_timestamp = DateValue::lockCurrentTimestamp();
    }

    protected function tearDown(): void
    {
        DateValue::unlockCurrentTimestamp();

        parent::tearDown();
    }

    public function pass(string $message = ''): void
    {
        $this->assertTrue(true, $message);
    }

    /**
     * Asserts that two associative arrays are similar.
     *
     * Both arrays must have the same indexes with identical values
     * without respect to key ordering
     */
    public function assertArraySimilar(array $expected, array $array): void
    {
        $this->assertTrue(count(array_diff_key($array, $expected)) === 0);

        foreach ($expected as $key => $value) {
            if (is_array($value)) {
                $this->assertArraySimilar($value, $array[$key]);
            } else {
                $this->assertContains($value, $array);
            }
        }
    }
}
