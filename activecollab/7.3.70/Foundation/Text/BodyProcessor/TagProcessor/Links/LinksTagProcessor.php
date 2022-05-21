<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\AuthenticationInterface;
use ActiveCollab\Foundation\App\RootUrl\RootUrlInterface;
use ActiveCollab\Foundation\Models\IdentifiableInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\Resolver\TextReplacementResolverInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\TextReplacement;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\Links\TextReplacement\TextReplacementInterface;
use ActiveCollab\Foundation\Text\BodyProcessor\TagProcessor\TagProcessor;
use ActiveCollab\Foundation\Text\HtmlCleaner\AllowedTag\AllowedTag;
use ActiveCollab\Foundation\Urls\Expander\UrlExpanderInterface;
use ActiveCollab\Foundation\Urls\ExternalUrl;
use ActiveCollab\Foundation\Urls\ExternalUrlInterface;
use ActiveCollab\Foundation\Urls\Factory\UrlFactoryInterface;
use ActiveCollab\Foundation\Urls\IgnoredDomainsResolver\IgnoredDomainsResolverInterface;
use ActiveCollab\Foundation\Urls\InternalUrl;
use ActiveCollab\Foundation\Urls\Router\MatchedRoute\MatchedEntityInterface;
use ActiveCollab\Foundation\Urls\Router\UrlMatcher\UrlMatcherInterface;
use ActiveCollab\Foundation\Urls\UrlInterface;
use ActiveCollab\Foundation\Wrappers\DataObjectPool\DataObjectPoolInterface;
use DataObject;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use simple_html_dom;
use simple_html_dom_node;

class LinksTagProcessor extends TagProcessor
{
    private RootUrlInterface $root_url;
    private UrlFactoryInterface $url_factory;
    private UrlMatcherInterface $url_matcher;
    private UrlExpanderInterface $url_expander;
    private DataObjectPoolInterface $data_object_pool;
    private AuthenticationInterface $authentication;
    private TextReplacementResolverInterface $text_replacement_resolver;
    private IgnoredDomainsResolverInterface $ignored_domains_resolver;
    private array $entities_to_expand;
    private LoggerInterface $logger;

    public function __construct(
        RootUrlInterface $root_url,
        UrlFactoryInterface $url_factory,
        UrlMatcherInterface $url_matcher,
        UrlExpanderInterface $url_expander,
        DataObjectPoolInterface $data_object_pool,
        AuthenticationInterface $authentication,
        TextReplacementResolverInterface $text_replacement_resolver,
        IgnoredDomainsResolverInterface $ignored_domains_resolver,
        array $entities_to_expand,
        LoggerInterface $logger
    ) {
        $this->root_url = $root_url;
        $this->url_factory = $url_factory;
        $this->url_matcher = $url_matcher;
        $this->url_expander = $url_expander;
        $this->data_object_pool = $data_object_pool;
        $this->authentication = $authentication;
        $this->text_replacement_resolver = $text_replacement_resolver;
        $this->ignored_domains_resolver = $ignored_domains_resolver;
        $this->entities_to_expand = $entities_to_expand;
        $this->logger = $logger;
    }

    public function getAllowedTags(): array
    {
        return [
            new AllowedTag(
                'a',
                'data-entity-type',
                'data-entity-id',
                'data-replacement',
                'data-replacement-suffix'
            ),
        ];
    }

    public function processForStorage(simple_html_dom $dom): array
    {
        $result = [];

        /** @var simple_html_dom_node[] $elements */
        $elements = $dom->find('a[href]');

        if (empty($elements)) {
            return $result;
        }

        foreach ($elements as $element) {
            $url = $this->getUrlFromHref((string) $element->attr['href']);

            if (empty($url)) {
                continue;
            }

            if ($url instanceof InternalUrl) {
                if ($url->isModal()) {
                    $view_url = $url->getModalArguments()->getViewUrl();

                    $this->logger->debug(
                        'Updating modal URL from {modal_url} to {view_url}',
                        [
                            'modal_url' => $url->getUrl(),
                            'view_url' => $view_url,
                        ]
                    );

                    $element->attr['href'] = $view_url;
                } else {
                    $element->attr['href'] = $url->getUrl();
                }

                $matched_route = $this->url_matcher->matchUrl($element->attr['href']);

                if ($matched_route instanceof MatchedEntityInterface) {
                    $this->logger->debug(
                        'Link {url} is a {entity_type} resource URL',
                        [
                            'url' => $element->attr['href'],
                            'entity_type' => $matched_route->getEntityName(),
                            'entity_id' => $matched_route->getEntityId(),
                        ]
                    );

                    $element->setAttribute('data-entity-type', $matched_route->getEntityName());
                    $element->setAttribute('data-entity-id', $matched_route->getEntityId());

                    $text_replacement = $this->getTextReplacement(
                        $url->getUrl(),
                        $element->attr['href'],
                        $element->innertext(),
                        $this->authentication->getAuthenticatedUser(),
                        $matched_route->getEntityName(),
                        $matched_route->getEntityId(),
                        !empty($element->innerhtml)
                    );
                } else {
                    $this->clearAttributes($element, 'data-entity-type', 'data-entity-id');

                    $text_replacement = $this->getTextReplacement(
                        $url->getUrl(),
                        $element->attr['href'],
                        $element->innertext(),
                        $this->authentication->getAuthenticatedUser(),
                        null,
                        null,
                        !empty($element->innerhtml)
                    );
                }

                if ($text_replacement) {
                    $element->innertext = clean(
                        $text_replacement->getReplacement() . $text_replacement->getSuffix()
                    );

                    $element->setAttribute('data-replacement', $text_replacement->getReplacementType());

                    if ($text_replacement->getSuffix()) {
                        $element->setAttribute('data-replacement-suffix', $text_replacement->getSuffix());
                    } elseif ($element->hasAttribute('data-replacement-suffix')) {
                        $element->removeAttribute('data-replacement-suffix');
                    }
                } else {
                    $this->clearAttributes($element, 'data-replacement', 'data-replacement-suffix');
                }
            } else {
                if ($url instanceof ExternalUrl) {
                    $element->setAttribute('target', '_blank');
                    $element->setAttribute('rel', 'noopener noreferrer');

                    if (!$this->ignored_domains_resolver->isDomainIgnored($url->getDomain())) {
                        $this->logger->info(
                            'External URL "{external_url}" found.',
                            [
                                'external_url' => $url->getUrl(),
                                'domain' => $url->getDomain(),
                            ]
                        );
                    }
                }

                $this->clearAttributes(
                    $element,
                    'data-entity-type',
                    'data-entity-id',
                    'data-replacement',
                    'data-replacement-suffix'
                );
            }

            $result[] = new LinkArtifact($url->getUrl());
        }

        return $result;
    }

    private function clearAttributes(simple_html_dom_node $element, string ...$attributes)
    {
        foreach ($attributes as $attribute) {
            if ($element->hasAttribute($attribute)) {
                $element->removeAttribute($attribute);
            }
        }
    }

    public function processForDisplay(simple_html_dom $dom, IdentifiableInterface $context, string $display): void
    {
        /** @var simple_html_dom_node[] $elements */
        $elements = $dom->find('a[href]');

        if ($elements) {
            foreach ($elements as $element) {
                $url = $this->getUrlFromHref($element->attr['href']);

                if ($url instanceof ExternalUrlInterface) {
                    $element->setAttribute('target', '_blank');
                    $element->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $replacement = $element->getAttribute('data-replacement');

                    if (in_array($replacement, TextReplacementInterface::REPLACEMENTS)) {
                        $entity_type = $element->getAttribute('data-entity-type');
                        $entity_id = (int) $element->getAttribute('data-entity-id');

                        if ($entity_type && $entity_id) {
                            $entity = $this->data_object_pool->get($entity_type, $entity_id);

                            if ($entity instanceof DataObject) {
                                $element->setAttribute('href', $entity->getViewUrl());

                                $suffix = $element->hasAttribute('data-replacement-suffix')
                                    ? trim($element->getAttribute('data-replacement-suffix'))
                                    : '';

                                $element->innertext = $this->text_replacement_resolver->getTextReplacement(
                                    $entity,
                                    $replacement,
                                    $suffix
                                );
                            }
                        }
                    }
                }
            }
        }

        $this->url_expander->expandUrlsInDom($dom, $context, $display);
    }

    private function getUrlFromHref(string $href): ?UrlInterface
    {
        $url_to_analyze = $href;

        if (empty($url_to_analyze)) {
            $url_to_analyze = $this->root_url->getUrl();
        } elseif (!filter_var($url_to_analyze, FILTER_VALIDATE_URL)) {
            $url_to_analyze = $this->root_url->expandRelativeUrl($url_to_analyze);
        }

        if (!filter_var($url_to_analyze, FILTER_VALIDATE_URL)) {
            $this->logNoticeAboutInvalidUrl($href);

            return null;
        }

        foreach ($this->getSuffixes() as $suffix) {
            if (str_ends_with($href, $suffix)) {
                $url_to_analyze = rtrim($href, $suffix);
                break;
            }
        }

        try {
            $url = $this->url_factory->createFromUrl($url_to_analyze);
        } catch (InvalidArgumentException $e) {
            $this->logNoticeAboutInvalidUrl($href);

            return null;
        }

        if ($url instanceof InternalUrl) {
            return $url;
        }

        try {
            return $this->url_factory->createFromUrl($href);
        } catch (InvalidArgumentException $e) {
            $this->logNoticeAboutInvalidUrl($href);

            return null;
        }
    }

    private function getTextReplacement(
        string $start_url,
        string $new_url,
        string $inner_text,
        ?AuthenticatedUserInterface $authenticated_user,
        ?string $entity_type,
        ?int $entity_id,
        bool $has_inner_html
    ): ?TextReplacementInterface
    {
        if ($has_inner_html) {
            return null;
        }

        $entity_text = null;

        if ($entity_type && $entity_id) {
            if (in_array($entity_type, $this->getEntitiesToExpand())) {
                $entity = $this->data_object_pool->get($entity_type, $entity_id);

                if ($entity instanceof DataObject
                    && method_exists($entity, 'getName')
                    && $this->shouldReplaceWithEntityName($entity, $authenticated_user)
                ) {
                    $entity_text = (string) $entity->getName();
                }
            } else {
                $this->logger->debug(
                    'Link {start_url} (via {new_url}) maps to {entity_type} #{entity_id}, but we are not expanding resources of this type.',
                    [
                        'start_url' => $start_url,
                        'new_url' => $new_url,
                        'entity_type' => $entity_type,
                        'entity_id' => $entity_id,
                    ]
                );
            }
        }

        $suffix = $this->getTextReplacementSuffix($start_url, $inner_text);

        if ($this->isInnerTextUrlOrName($inner_text, $start_url, $entity_text, $suffix)) {
            if ($entity_text) {
                $this->logger->debug(
                    'Updating inner text "{inner_text}" to "{entity_name}{suffix}".',
                    [
                        'inner_text' => $inner_text,
                        'entity_name' => $entity_text,
                        'entity_id' => $entity_id,
                        'suffix' => $suffix,
                    ]
                );

                return new TextReplacement(
                    TextReplacementInterface::REPLACE_WITH_NAME,
                    $entity_text,
                    $suffix
                );
            } else {
                $this->logger->debug(
                    'Updating inner text "{inner_text}" to "{new_url}{suffix}".',
                    [
                        'inner_text' => $inner_text,
                        'new_url' => $new_url,
                        'suffix' => $suffix,
                    ]
                );

                return new TextReplacement(
                    TextReplacementInterface::REPLACE_WITH_URL,
                    $new_url,
                    $suffix
                );
            }
        } else {
            $this->logger->debug(
                'Skipping update of "inner_text" inner text because it is different than {start_url}.',
                [
                    'inner_text' => $inner_text,
                    'start_url' => $start_url,
                ]
            );
        }

        return null;
    }

    private function shouldReplaceWithEntityName(
        DataObject $entity,
        ?AuthenticatedUserInterface $authenticated_user
    ): bool
    {
        return $authenticated_user
            && method_exists($entity, 'canView')
            && $entity->canView($authenticated_user);
    }

    private function isInnerTextUrlOrName(
        string $inner_text,
        string $start_url,
        ?string $entity_text,
        string $suffix
    ): bool
    {
        if ($inner_text === $start_url || $inner_text === $start_url . $suffix) {
            return true;
        }

        if ($entity_text !== null && ($inner_text === $entity_text || $inner_text === $entity_text . $suffix)) {
            return true;
        }

        return false;
    }

    private function getTextReplacementSuffix(string $start_url, string $inner_text): string
    {
        if ($inner_text !== $start_url && str_starts_with($inner_text, $start_url)) {
            foreach ($this->getSuffixes() as $suffix) {
                if ($inner_text === $start_url . $suffix) {
                    return $suffix;
                }
            }
        }

        return '';
    }

    private function getSuffixes(): array
    {
        return [
            '.',
            ',',
            ':',
            ';',
        ];
    }

    private function getEntitiesToExpand(): array
    {
        return $this->entities_to_expand;
    }

    private function logNoticeAboutInvalidUrl(string $href): void
    {
        $this->logger->notice(
            'Invalid URL "{invalid_url}" found and skipped.',
            [
                'invalid_url' => $href,
            ]
        );
    }
}
