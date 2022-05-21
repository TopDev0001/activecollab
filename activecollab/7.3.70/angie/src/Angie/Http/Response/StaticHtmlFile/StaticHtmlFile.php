<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace Angie\Http\Response\StaticHtmlFile;

class StaticHtmlFile implements StaticHtmlFileInterface
{
    private string $path;
    private array $options;

    public function __construct(string $path, array $options = [])
    {
        $this->path = $path;
        $this->options = $options;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getContent(): string
    {
        $content = file_get_contents($this->getPath());

        if (!empty($this->getOptions())) {
            foreach ($this->getOptions() as $key => $val) {
                $content = str_replace($key, $val, $content);
            }
        }

        return $content;
    }
}
