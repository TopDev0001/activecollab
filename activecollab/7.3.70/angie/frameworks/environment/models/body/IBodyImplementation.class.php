<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

use ActiveCollab\Foundation\History\Renderers\BodyHistoryFieldRenderer;
use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessorInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\InlineImages\InlineImageArtifact;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Mentions\MentionArtifact;
use ActiveCollab\Module\System\Utils\BodyProcessorResolver\BodyProcessorResolverInterface;
use Angie\HTML;

trait IBodyImplementation
{
    private array $inline_image_codes = [];
    private array $new_mentions = [];

    public function registerIBodyImplementation(): void
    {
        if ($this instanceof IHistory) {
            $this->addHistoryFields('body');
        }

        $this->registerEventHandler(
            'on_history_field_renderers',
            function (& $renderers) {
                $renderers['body'] = new BodyHistoryFieldRenderer();
            }
        );

        $this->registerEventHandler(
            'on_json_serialize',
            function (array & $result) {
                $result['body'] = $this->getBodyForEditor();
                $result['body_formatted'] = $this->getFormattedBody();

                if ($this->includeBodyModeInJson()) {
                    $result['body_mode'] = $this->getBodyMode();
                }

                if ($this->includePlainTextBodyInJson()) {
                    $result['body_plain_text'] = $this->getPlainTextBody();
                }
            }
        );

        $this->registerEventHandler(
            'on_after_save',
            function ($is_new, $modifications) {
                if ($this->supportsInlineImages()) {
                    if (!$is_new && !empty($modifications['body'])) {
                        /** @var Attachment[] $attachments */
                        $attachments = $this->getInlineAttachments();

                        if ($attachments) {
                            foreach ($attachments as $attachment) {
                                if (strpos($modifications['body'][1], 'image-type="attachment" object-id="' . $attachment->getId() . '"') === false) {
                                    $attachment->delete(true);
                                }
                            }
                        }
                    }

                    if (count($this->inline_image_codes)) {
                        $files = UploadedFiles::findByCodes($this->inline_image_codes);

                        if ($files) {
                            $body = $this->getBody();

                            DB::transact(
                                function () use ($files, &$body) {
                                    foreach ($files as $file) {
                                        /** @var $this IAttachments */
                                        if ($attachment = $this->attachUploadedFile($file, IAttachments::INLINE)) {
                                            $body = str_replace(
                                                sprintf('object-id="%s"', $file->getCode()),
                                                sprintf('object-id="%d"', $attachment->getId()),
                                                $body
                                            );
                                        }
                                    }
                                },
                                'Attaching files'
                            );

                            $this->setBody($body);
                            $this->save();
                        }

                        $this->inline_image_codes = [];
                    }
                }
            }
        );

        $this->registerEventHandler(
            'on_prepare_field_value_before_set',
            function ($field, & $value) {
                if ($field == 'body' && is_string($value) && $value) {
                    $newly_mentioned_users = [];
                    $inline_image_codes = [];

                    $processed_body = $this->getBodyProcessor()->processForStorage($value);

                    /** @var MentionArtifact $mention */
                    foreach ($processed_body->getArtifactsByType(MentionArtifact::class) as $mention) {
                        $newly_mentioned_users[] = $mention->getUserId();
                    }

                    /** @var InlineImageArtifact $inline_image */
                    foreach ($processed_body->getArtifactsByType(InlineImageArtifact::class) as $inline_image) {
                        $inline_image_codes[] = $inline_image->getInlineImageCode();
                    }

                    $value = $processed_body->getProcessedHtml();

                    if (count($newly_mentioned_users)) {
                        sort($newly_mentioned_users);
                    }

                    $this->new_mentions = $newly_mentioned_users;
                    $this->inline_image_codes = $inline_image_codes;
                }
            }
        );
    }

    private function getBodyProcessor(): BodyProcessorInterface
    {
        return AngieApplication::getContainer()
            ->get(BodyProcessorResolverInterface::class)
                ->resolve($this->supportsInlineImages());
    }

    abstract protected function registerEventHandler(string $event, callable $handler): void;

    /**
     * Return value of body field.
     *
     * @return string
     */
    abstract public function getBody();

    public function getBodyForEditor()
    {
        return $this->getBodyProcessor()
            ->processForEditor(
                (string) $this->getBody(),
                $this,
            )
            ->getProcessedHtml();
    }

    public function getFormattedBody(string $display = BodyProcessorInterface::DISPLAY_SCEEEN): string
    {
        return AngieApplication::cache()->getByObject(
            $this,
            sprintf('formatted_body_for_%s', $display),
            function () use ($display) {
                return $this->getBodyProcessor()->processForDisplay(
                    (string) $this->getBody(),
                    $this,
                    $display
                )->getProcessedHtml();
            }
        );
    }

    /**
     * Include plain text version of body in the JSON response.
     *
     * @return bool
     */
    protected function includePlainTextBodyInJson()
    {
        return false;
    }

    protected function includeBodyModeInJson(): bool
    {
        return true;
    }

    public function getPlainTextBody(): string
    {
        return AngieApplication::cache()->getByObject(
            $this,
            'plain_text_body',
            function () {
                return (string) HTML::toPlainText($this->getFormattedBody());
            }
        );
    }

    // ---------------------------------------------------
    //  Expectations
    // ---------------------------------------------------

    private function supportsInlineImages(): bool
    {
        return $this instanceof IAttachments;
    }

    /**
     * Set value of body field.
     *
     * @param  string $value
     * @return string
     */
    abstract public function setBody($value);

    public function getNewMentions(): array
    {
        return $this->new_mentions;
    }

    /**
     * Set specific field value.
     *
     * Set value of the $field. This function will make sure that everything
     * runs fine - modifications are saved, in case of primary key old value
     * will be remembered in case we need to update the row and so on
     *
     * @param  mixed             $value
     * @return mixed
     * @throws InvalidParamError
     */
    abstract public function setFieldValue(string $field, $value);
}
