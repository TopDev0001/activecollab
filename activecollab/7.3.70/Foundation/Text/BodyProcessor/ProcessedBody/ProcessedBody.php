<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\ProcessedBody;

use ActiveCollab\Foundation\Text\BodyProcessor\Artifact\ArtifactInterface;

class ProcessedBody implements ProcessedBodyInterface
{
    private $processed_html;
    private $artifacts = [];

    public function __construct(string $processed_html, ArtifactInterface ...$artifacts)
    {
        $this->processed_html = $processed_html;

        foreach ($artifacts as $artifact) {
            $artifact_type = get_class($artifact);

            if (empty($this->artifacts[$artifact_type])) {
                $this->artifacts[$artifact_type] = [];
            }

            $this->artifacts[$artifact_type][] = $artifact;
        }
    }

    public function getProcessedHtml(): string
    {
        return $this->processed_html;
    }

    public function getArtifacts(): array
    {
        $result = [];

        foreach ($this->artifacts as $artifats) {
            $result = array_merge($result, $artifats);
        }

        return $result;
    }

    public function getArtifactsByType(string $artifact_type): array
    {
        return $this->artifacts[$artifact_type] ?? [];
    }
}
