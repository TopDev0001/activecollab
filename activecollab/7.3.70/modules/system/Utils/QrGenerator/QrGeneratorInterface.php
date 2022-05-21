<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\QrGenerator;

interface QrGeneratorInterface
{
    public function generate(string $data): QrGeneratorInterface;

    /**
     * Get binary data as string.
     */
    public function writeString(): string;

    /**
     * Base64Encoded, useful  for using with <img> tag in html.
     */
    public function writeDataUri(): string;

    /**
     * Generate file on disk.
     *
     * @return mixed
     */
    public function writeFile(string $path): void;
}
