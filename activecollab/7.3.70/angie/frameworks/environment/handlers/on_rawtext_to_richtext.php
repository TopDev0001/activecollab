<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

/**
 * Convert environment module rawtext to richtext.
 *
 * @package angie.framework.environment
 * @subpackage handlers
 */

/**
 * do rawtext to richtext conversion.
 *
 * @param simple_html_dom $parser
 * @param string|null     $for
 * @param string|null     $parent_type
 * @param int|null        $parent_id
 */
function environment_handle_on_rawtext_to_richtext($parser, $for = null, $parent_type = null, $parent_id = null)
{
    // <a object-id="*" object-class="*">User: Goran Radulović</a>
    $object_links = $parser->find('a[object-id][object-class]');
    if (is_foreachable($object_links)) {
        foreach ($object_links as $object_link) {
            $object_id = array_var($object_link->attr, 'object-id', null);
            $object_class = Angie\Inflector::camelize(array_var($object_link->attr, 'object-class', null));

            if ($object_id && $object_class) {
                try {
                    $object = DataObjectPool::get($object_class, $object_id);

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
    $video_placeholders = $parser->find('div[placeholder-type=video]');
    if ($video_placeholders && is_foreachable($video_placeholders)) {
        foreach ($video_placeholders as $video_placeholder) {
            $video_service = array_var($video_placeholder->attr, 'placeholder-extra', 'youtube');
            $video_id = array_var($video_placeholder->attr, 'placeholder-object-id', null);

            if ($for === 'notification' || $for === 'printer') {
                if ($video_service == 'youtube') {
                    $video_placeholder->outertext = '<p>' . lang('Video') . ': <a href="http://www.youtube.com/watch?v=' . clean($video_id) . '" target="_blank">http://www.youtube.com/watch?v=' . clean($video_id) . '</p>';
                } else {
                    $video_placeholder->outertext = '<p>Unknown video service</p>';
                }
            } else {
                if ($video_service == 'youtube') {
                    $video_placeholder->outertext = '<div class="youtube_video_wrapper" style="text-align: center;"><div class="youtube_video_wrapper_innner" style="margin: 8px auto"><iframe width="550" height="335" src="//www.youtube.com/embed/' . clean($video_id) . '?theme=light&wmode=opaque" frameborder="0" allowfullscreen></iframe></div></div>';
                } else {
                    $video_placeholder->outertext = '<p>Unknown video service</p>';
                }
            }
        }
    }

    // <img object-id="*" image-type="attachment">
    $inline_image_placeholder_placeholders = $parser->find('img[image-type=attachment]');
    if (is_foreachable($inline_image_placeholder_placeholders)) {
        if ($for === 'notification') {
            $max_inline_object_width = 500;
            $max_inline_object_height = 500;
        } else {
            $max_inline_object_width = 800;
            $max_inline_object_height = 800;
        }

        foreach ($inline_image_placeholder_placeholders as $inline_image_placeholder) {
            $image_id = array_var($inline_image_placeholder->attr, 'object-id', null);

            if ($image_id) {
                try {
                    $inline_image = Attachments::getInlineImageDetailsByParent($image_id, $parent_type, $parent_id);

                    if (empty($inline_image['id'])) {
                        throw new InvalidParamError(
                            'id',
                            array_key_exists('id', $inline_image) ? $inline_image['id'] : null
                        );
                    }

                    if (empty($inline_image['name'])) {
                        throw new InvalidParamError(
                            'name',
                            array_key_exists('name', $inline_image) ? $inline_image['name'] : null
                        );
                    }

                    if (empty($inline_image['thumbnail_url'])) {
                        throw new InvalidParamError(
                            'thumbnail_url',
                            array_key_exists('thumbnail_url', $inline_image) ? $inline_image['thumbnail_url'] : null
                        );
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

                    if ($inline_image_placeholder->parent && $inline_image_placeholder->parent->tag && $inline_image_placeholder->parent->tag == 'a') {
                        $inline_image_placeholder->outertext = '<div class="rich_text_inline_image_wrapper"><img src="' . clean($inline_image['thumbnail_url']) . '" alt="' . clean($inline_image['name']) . '" attachment-id="' . $inline_image['id'] . '" /></div>';
                    } else {
                        if (!isset($inline_image['download_url'])) {
                            throw new InvalidParamError('download_url', $inline_image['download_url']);
                        }
                        $inline_image_placeholder->outertext = '<div class="rich_text_inline_image_wrapper"><a href="' . clean($inline_image['download_url']) . '" target="_blank"><img src="' . clean($inline_image['thumbnail_url']) . '" alt="' . clean($inline_image['name']) . '" attachment-id="' . $inline_image['id'] . '" /></a></div>';
                    }
                } catch (Exception $e) {
                    $inline_image_placeholder->outertext = '';
                }
            } else {
                $inline_image_placeholder->outertext = '';
            }
        }
    }

    // <img object-id="*" image-type="attachment">
    if ($for === 'notification') {
        $mentions = $parser->find('span.mention');

        if ($mentions && is_foreachable($mentions)) {
            foreach ($mentions as $mention) {
                if ($mention->innertext) {
                    $inner_text = $mention->innertext;
                } elseif ($mention->plaintext) {
                    $inner_text = $mention->plaintext;
                } else {
                    $inner_text = 'mention';
                }

                $mention->outertext = '<span style="background-color: #D9EEFF; padding: 0 2px; border: 1px solid #AEBFCC; border-radius: 3px;">@' . clean($inner_text) . '</span>';
            }
        }
    }
}
