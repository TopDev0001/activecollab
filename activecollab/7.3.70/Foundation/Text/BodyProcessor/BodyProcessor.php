<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\ProcessedBody\ProcessedBody;
use ActiveCollab\Foundation\Text\BodyProcessor\ProcessedBody\ProcessedBodyInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\TagProcessorInterface;
use ActiveCollab\Foundation\Text\HtmlCleaner\HtmlCleanerInterface;
use ActiveCollab\Foundation\Text\HtmlToDomConverter\HtmlToDomConverterInterface;
use simple_html_dom;

class BodyProcessor implements BodyProcessorInterface
{
    private $html_cleaner;
    private $html_to_dom_converter;
    private $tag_processors;

    public function __construct(
        HtmlCleanerInterface $html_cleaner,
        HtmlToDomConverterInterface $html_to_dom_converter,
        TagProcessorInterface ...$tag_processors
    )
    {
        $this->html_cleaner = $html_cleaner;
        $this->tag_processors = $tag_processors;

        foreach ($this->tag_processors as $tag_processor) {
            foreach ($tag_processor->getAllowedTags() as $allowed_tag) {
                $this->html_cleaner->allowTag($allowed_tag);
            }
        }
        $this->html_to_dom_converter = $html_to_dom_converter;
    }

    public function getHtmlCleaner(): HtmlCleanerInterface
    {
        return $this->html_cleaner;
    }

    public function processForEditor(
        string $stored_body,
        IdentifiableInterface $context
    ): ProcessedBodyInterface
    {
        if (trim($stored_body)) {
            $dom = $this->html_to_dom_converter->htmlToDom($stored_body);

            foreach ($this->tag_processors as $tag_processor) {
                $tag_processor->processForEditor($dom, $context);
            }

            return new ProcessedBody((string) $dom);
        }

        return new ProcessedBody($stored_body);
    }

    public function processForStorage(string $raw_body): ProcessedBodyInterface
    {
        $artifacts = [];

        $processed_body = $this->html_cleaner->cleanUp(
            $raw_body,
            function (simple_html_dom $dom) use (&$artifacts) {
                foreach ($this->tag_processors as $tag_processor) {
                    $tag_artifacts = $tag_processor->processForStorage($dom);

                    if (!empty($tag_artifacts)) {
                        $artifacts = array_merge($artifacts, $tag_artifacts);
                    }
                }
            }
        );

        return new ProcessedBody($processed_body, ...$artifacts);
    }

    public function processForDisplay(
        string $stored_body,
        IdentifiableInterface $context,
        string $display = BodyProcessorInterface::DISPLAY_SCEEEN
    ): ProcessedBodyInterface
    {
        if (trim($stored_body)) {
            $dom = $this->html_to_dom_converter->htmlToDom($stored_body);

            foreach ($this->tag_processors as $tag_processor) {
                $tag_processor->processForDisplay($dom, $context, $display);
            }

            return new ProcessedBody((string) $dom);
        }

        return new ProcessedBody($stored_body);
    }
}
