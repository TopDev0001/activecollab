<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\InlineImages;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessorInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\TagProcessor;
use ActiveCollab\Foundation\Text\HtmlCleaner\AllowedTag\AllowedTag;
use ActiveCollab\Foundation\Urls\Expander\UrlExpanderInterface;
use ActiveCollab\Foundation\Urls\Factory\UrlFactoryInterface;
use ActiveCollab\Foundation\Urls\InternalUrl;
use ActiveCollab\Foundation\Urls\Services\WarehouseUrl;
use ActiveCollab\Foundation\Urls\Services\WarehouseUrlInterface;
use ActiveCollab\Module\System\Utils\InlineImageDetailsResolver\InlineImageDetailsResolverInterface;
use Exception;
use InvalidParamError;
use simple_html_dom;
use Thumbnails;

class InlineImagesTagProcessor extends TagProcessor
{
    private InlineImageDetailsResolverInterface $inline_image_details_resolver;
    private UrlFactoryInterface $url_factory;
    private UrlExpanderInterface $url_expander;

    public function __construct(
        InlineImageDetailsResolverInterface $inline_image_details_resolver,
        UrlFactoryInterface $url_factory,
        UrlExpanderInterface $url_expander
    ) {
        $this->inline_image_details_resolver = $inline_image_details_resolver;
        $this->url_factory = $url_factory;
        $this->url_expander = $url_expander;
    }

    public function getAllowedTags(): array
    {
        return [
            new AllowedTag('img', 'image-type', 'object-id'),
        ];
    }

    public function processForEditor(
        simple_html_dom $dom,
        IdentifiableInterface $context
    ): void
    {
        $images = $dom->find('img');

        if ($images) {
            foreach ($images as $element) {
               $image_id = array_var($element->attr, 'object-id', null);
                if ($image_id) {
                    $parent_type = $context->getType();
                    $parent_id = $context->getId();

                    [
                        $max_inline_object_width,
                        $max_inline_object_height,
                    ] = $this->getAttachmentDimensions(BodyProcessorInterface::DISPLAY_SCEEEN);

                    $inline_image = $this->inline_image_details_resolver->getDetailsByParent(
                        $image_id,
                        $parent_type,
                        $parent_id
                    );

                    if (!empty($inline_image['thumbnail_url'])) {
                        $url = $inline_image['thumbnail_url'];

                        $url = str_replace(
                            '--WIDTH--',
                            (string) $max_inline_object_width,
                            $url
                        );
                        $url = str_replace(
                            '--HEIGHT--',
                            (string) $max_inline_object_height,
                            $url
                        );
                        $url = str_replace(
                            '--SCALE--',
                            Thumbnails::SCALE,
                            $url
                        );

                        $url = str_replace(ROOT_URL, '', $url);

                        $element->attr['src'] = $url;
                    } else {
                        $element->outertext = $this->getImageDeletePlaceholder($element->attr['alt'] ?? '');
                    }
                } elseif ((filter_var($element->attr['src'] ?? null, FILTER_VALIDATE_URL))) {
                    $url = $this->url_factory->createFromUrl($element->attr['src']);

                    if ($url instanceof WarehouseUrl) {
                        $element->attr['src'] = $url->getExtendedUrl([
                            'intent' => WarehouseUrlInterface::FILE_EXTENSION_THUMBNAILS,
                        ]);
                    }
                }
            }
        }
    }

    public function processForStorage(simple_html_dom $dom): array
    {
        $result = [];

        $elements = $dom->find('img');

        if ($elements) {
            foreach ($elements as $element) {
                $uploaded_image_code = !empty($element->attr['image-type']) && $element->attr['image-type'] === 'attachment'
                    ? array_var($element->attr, 'object-id')
                    : null;

                if (filter_var($element->attr['src'] ?? null, FILTER_VALIDATE_URL) || $this->isLocalFile($element->attr['src'])) {
                    $url = $this->url_factory->createFromUrl(htmlspecialchars_decode($element->attr['src']));

                    if ($url instanceof WarehouseUrl) {
                        $element->attr['src'] = $url->removeQueryElement('intent');
                    } elseif ($url instanceof InternalUrl) {
                        $element->attr['src'] = $url->removeQueryElement('i');
                    }
                }

                if ($uploaded_image_code && strlen($uploaded_image_code) == 40) {
                    $result[] = new InlineImageArtifact($uploaded_image_code);
                }
            }
        }

        return $result;
    }

    public function processForDisplay(simple_html_dom $dom, IdentifiableInterface $context, string $display): void
    {
        $inline_image_placeholder_placeholders = $dom->find('img[image-type=attachment]');
        if (!empty($inline_image_placeholder_placeholders)) {
            $parent_type = $context->getType();
            $parent_id = $context->getId();

            [
                $max_inline_object_width,
                $max_inline_object_height,
            ] = $this->getAttachmentDimensions($display);

            foreach ($inline_image_placeholder_placeholders as $inline_image_placeholder) {
                $image_id = array_var($inline_image_placeholder->attr, 'object-id');

                if ($image_id) {
                    try {
                        $inline_image = $this->inline_image_details_resolver->getDetailsByParent(
                            $image_id,
                            $parent_type,
                            $parent_id
                        );

                        if (empty($inline_image['id'])) {
                            throw new InvalidParamError('id', array_key_exists('id', $inline_image) ? $inline_image['id'] : null);
                        }

                        if (empty($inline_image['name'])) {
                            throw new InvalidParamError('name', array_key_exists('name', $inline_image) ? $inline_image['name'] : null);
                        }

                        if (empty($inline_image['thumbnail_url'])) {
                            throw new InvalidParamError('thumbnail_url', array_key_exists('thumbnail_url', $inline_image) ? $inline_image['thumbnail_url'] : null);
                        }

                        $inline_image['thumbnail_url'] = str_replace(
                            '--WIDTH--',
                            (string) $max_inline_object_width,
                            $inline_image['thumbnail_url']
                        );
                        $inline_image['thumbnail_url'] = str_replace(
                            '--HEIGHT--',
                            (string) $max_inline_object_height,
                            $inline_image['thumbnail_url']
                        );
                        $inline_image['thumbnail_url'] = str_replace(
                            '--SCALE--',
                            Thumbnails::SCALE,
                            $inline_image['thumbnail_url']
                        );

                        if ($inline_image_placeholder->parent
                            && $inline_image_placeholder->parent->tag
                            && $inline_image_placeholder->parent->tag == 'a'
                        ) {
                            $inline_image_placeholder->outertext = sprintf(
                                '<div class="rich_text_inline_image_wrapper"><img src="%s" alt="%s" attachment-id="%d" /></div>',
                                clean($inline_image['thumbnail_url']),
                                clean($inline_image['name']),
                                $inline_image['id']
                            );
                        } else {
                            if (!isset($inline_image['download_url'])) {
                                throw new InvalidParamError('download_url', $inline_image['download_url']);
                            }
                            $inline_image_placeholder->outertext = sprintf(
                                '<div class="rich_text_inline_image_wrapper"><a href="%s" target="_blank"><img src="%s" alt="%s" attachment-id="%d" /></a></div>',
                                clean($inline_image['download_url']),
                                clean($inline_image['thumbnail_url']),
                                clean($inline_image['name']),
                                $inline_image['id']
                            );
                        }
                    } catch (Exception $e) {
                        $inline_image_placeholder->outertext = $this->getImageDeletePlaceholder($inline_image_placeholder->attr['alt'] ?? '');
                    }
                } else {
                    $inline_image_placeholder->outertext = $this->getImageDeletePlaceholder($inline_image_placeholder->attr['alt'] ?? '');
                }
            }
        }
    }

    private function getAttachmentDimensions(string $display): array
    {
        if ($display === BodyProcessorInterface::DISPLAY_EMAIL) {
            $max_inline_object_width = 500;
            $max_inline_object_height = 500;
        } else {
            $max_inline_object_width = 800;
            $max_inline_object_height = 800;
        }

        return [
            $max_inline_object_width,
            $max_inline_object_height,
        ];
    }

    private function isLocalFile(?string $src): bool
    {
        if (is_null($src)) {
            return false;
        }

        $expected_proxy_url_bit = '/proxy.php?proxy';

        return substr($src, 0, strlen($expected_proxy_url_bit)) === $expected_proxy_url_bit;
    }

    private function getImageDeletePlaceholder(?string $image_name = ''): string
    {
        if (!empty($image_name)) {
            $image_name = "'{$image_name}'";
        }

        return "<span><em>&nbsp;[Image $image_name Deleted]&nbsp;</em></span>";
    }
}
