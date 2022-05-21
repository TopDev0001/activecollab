<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Legacy;

use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessorInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\TagProcessor;
use ActiveCollab\Foundation\Text\HtmlCleaner\AllowedTag\AllowedTag;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use Angie\Inflector;
use ApplicationObject;
use Exception;
use InvalidInstanceError;
use simple_html_dom;

class LegacyTagsProcessor extends TagProcessor
{
    private $data_object_pool;

    public function __construct(DataObjectPoolInterface $data_object_pool)
    {
        $this->data_object_pool = $data_object_pool;
    }

    public function getAllowedTags(): array
    {
        return [
            new AllowedTag('a', 'object-id', 'object-class'),
            new AllowedTag(
                'p',
                'placeholder-type',
                'placeholder-object-id',
                'placeholder-extra'
            ),
            new AllowedTag('pre', 'data-syntax'),
        ];
    }

    public function processForDisplay(simple_html_dom $dom, IdentifiableInterface $context, string $display): void
    {
        // <a object-id="*" object-class="*">User: Goran Radulović</a>
        $object_links = $dom->find('a[object-id][object-class]');
        if (is_foreachable($object_links)) {
            foreach ($object_links as $object_link) {
                $object_id = (int) array_var($object_link->attr, 'object-id', null);
                $object_class = Inflector::camelize(array_var($object_link->attr, 'object-class', null));

                if ($object_id && $object_class) {
                    try {
                        $object = $this->data_object_pool->get($object_class, $object_id);

                        if (!$object instanceof ApplicationObject) {
                            throw new InvalidInstanceError('object', $object, ApplicationObject::class);
                        }

                        if ($object_link->innertext) {
                            $inner_text = $object_link->innertext;
                        } elseif ($object_link->plaintext) {
                            $inner_text = $object_link->plaintext;
                        } else {
                            $inner_text = $object->getVerboseType() . ': ' . clean($object->getName());
                        }

                        // check if we need to open link in new window
                        $in_new_window = strtolower(array_var($object_link->attr, 'target', '')) == '_blank';
                        $object_link->outertext = '<a href="' . $object->getViewUrl() . '" ' . ($in_new_window ? 'target="_blank"' : '') . '>' . $inner_text . '</a>';
                    } catch (Exception $e) {
                        $object_link->outertext = '';
                    }
                } else {
                    $object_link->outertext = '';
                }
            }
        }

        // <div placeholder-type="video">
        $video_placeholders = $dom->find('div[placeholder-type=video]');
        if ($video_placeholders && is_foreachable($video_placeholders)) {
            foreach ($video_placeholders as $video_placeholder) {
                $video_service = array_var($video_placeholder->attr, 'placeholder-extra', 'youtube');
                $video_id = array_var($video_placeholder->attr, 'placeholder-object-id', null);

                if ($display === BodyProcessorInterface::DISPLAY_EMAIL) {
                    if ($video_service == 'youtube') {
                        $video_url = sprintf('http://www.youtube.com/watch?v=%s', $video_id);

                        $video_placeholder->outertext = sprintf(
                            '<p>%s: <a href="%s" target="_blank">%s</a></p>',
                            lang('Video'),
                            $video_url,
                            $video_url
                        );
                    } else {
                        $video_placeholder->outertext = '<p>Unknown video service</p>';
                    }
                } else {
                    if ($video_service == 'youtube') {
                        $video_placeholder->outertext = sprintf(
                            '<div class="youtube_video_wrapper" style="text-align: center;"><div class="youtube_video_wrapper_innner" style="margin: 8px auto"><iframe width="550" height="335" src="//www.youtube.com/embed/%s?theme=light&wmode=opaque" allowfullscreen></iframe></div></div>',
                            clean($video_id)
                        );
                    } else {
                        $video_placeholder->outertext = '<p>Unknown video service</p>';
                    }
                }
            }
        }
    }
}
