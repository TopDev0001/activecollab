<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Mail\Incoming\Processor\Address;

class Address implements AddressInterface
{
    private string $normalized_address;
    private ?string $tag;

    public function __construct(string $normalized_address, string $tag = null)
    {
        $this->normalized_address = $normalized_address;
        $this->tag = $tag;
    }

    public function getNormalizedAddress(): string
    {
        return $this->normalized_address;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getFullAddress(): string
    {
        if ($this->tag) {
            $at_pos = strpos($this->normalized_address, '@');

            if ($at_pos !== false) {
                return sprintf(
                    '%s+%s@%s',
                    mb_substr($this->normalized_address, 0, $at_pos),
                    $this->tag,
                    mb_substr($this->normalized_address, $at_pos + 1)
                );
            }
        }

        return $this->normalized_address;
    }
}
