<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Module\System\Utils\BodyProcessorResolver;

use ActiveCollab\Authentication\AuthenticationInterface;
use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessor;
use ActiveCollab\Foundation\Text\BodyProcessor\BodyProcessorInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\InlineImages\InlineImagesTagProcessor;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Legacy\LegacyTagsProcessor;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\LinksTagProcessor;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver\TextReplacementResolverInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Mentions\MentionsTagProcessor;
use ActiveCollab\Foundation\Text\HtmlCleaner\HtmlCleaner;
use ActiveCollab\Foundation\Text\HtmlToDomConverter\HtmlToDomConverter;
use ActiveCollab\Foundation\Urls\Expander\UrlExpanderInterface;
use ActiveCollab\Foundation\Urls\Factory\UrlFactory;
use ActiveCollab\Foundation\Urls\IgnoredDomainsResolver\IgnoredDomainsResolverInterface;
use ActiveCollab\Foundation\Urls\Router\UrlAssembler\UrlAssemblerInterface;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use ActiveCollab\Module\System\Utils\InlineImageDetailsResolver\InlineImageDetailsResolverInterface;
use ActiveCollab\Warehouse\Model\File;
use Discussion;
use Note;
use Project;
use Psr\Log\LoggerInterface;
use Task;

class BodyProcessorResolver implements BodyProcessorResolverInterface
{
    private DataObjectPoolInterface $data_object_pool;
    private AuthenticationInterface $authentication;
    private UrlMatcherInterface $url_matcher;
    private UrlAssemblerInterface $url_assembler;
    private UrlExpanderInterface $url_expander;
    private InlineImageDetailsResolverInterface $inline_image_details_resolver;
    private TextReplacementResolverInterface $text_replacement_resolver;
    private IgnoredDomainsResolverInterface $ignored_domains_resolver;
    private RootUrlInterface $root_url;
    private LoggerInterface $logger;

    public function __construct(
        DataObjectPoolInterface $data_object_pool,
        AuthenticationInterface $authentication,
        UrlMatcherInterface $url_matcher,
        UrlAssemblerInterface $url_assembler,
        UrlExpanderInterface $url_expander,
        InlineImageDetailsResolverInterface $inline_image_details_resolver,
        TextReplacementResolverInterface $text_replacement_resolver,
        IgnoredDomainsResolverInterface $ignored_domains_resolver,
        RootUrlInterface $root_url,
        LoggerInterface $logger
    )
    {
        $this->data_object_pool = $data_object_pool;
        $this->authentication = $authentication;
        $this->url_matcher = $url_matcher;
        $this->url_assembler = $url_assembler;
        $this->url_expander = $url_expander;
        $this->text_replacement_resolver = $text_replacement_resolver;
        $this->ignored_domains_resolver = $ignored_domains_resolver;
        $this->root_url = $root_url;
        $this->logger = $logger;
        $this->inline_image_details_resolver = $inline_image_details_resolver;
    }

    public function resolve(bool $with_inline_images = false): BodyProcessorInterface
    {
        return $with_inline_images
            ? $this->resolveWithInlineImages()
            : $this->resolveWithoutInlineImages();
    }

    private ?BodyProcessorInterface $body_processor_without_inline_images = null;

    private function resolveWithoutInlineImages(): BodyProcessorInterface
    {
        if (empty($this->body_processor_without_inline_images)) {
            $this->body_processor_without_inline_images = new BodyProcessor(
                new HtmlCleaner(),
                new HtmlToDomConverter(),
                ...$this->getBaseTagProcessors()
            );
        }

        return $this->body_processor_without_inline_images;
    }

    private ?BodyProcessorInterface $body_processor_with_inline_images = null;

    public function resolveWithInlineImages(): BodyProcessorInterface
    {
        if (empty($this->body_processor_with_inline_images)) {
            $this->body_processor_with_inline_images = new BodyProcessor(
                new HtmlCleaner(),
                new HtmlToDomConverter(),
                ...array_merge(
                    $this->getBaseTagProcessors(),
                    [
                        new InlineImagesTagProcessor(
                            $this->inline_image_details_resolver,
                            new UrlFactory($this->url_assembler, $this->root_url),
                            $this->url_expander
                        ),
                    ]
                )
            );
        }

        return $this->body_processor_with_inline_images;
    }

    private function getBaseTagProcessors(): array
    {
        return [
            new LegacyTagsProcessor($this->data_object_pool),
            new MentionsTagProcessor($this->logger),
            new LinksTagProcessor(
                $this->root_url,
                new UrlFactory($this->url_assembler, $this->root_url),
                $this->url_matcher,
                $this->url_expander,
                $this->data_object_pool,
                $this->authentication,
                $this->text_replacement_resolver,
                $this->ignored_domains_resolver,
                [
                    Project::class,
                    Task::class,
                    Discussion::class,
                    File::class,
                    Note::class,
                ],
                $this->logger
            ),
        ];
    }
}
