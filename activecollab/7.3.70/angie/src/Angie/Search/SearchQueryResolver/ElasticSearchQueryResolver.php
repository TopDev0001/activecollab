<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace Angie\Search\SearchQueryResolver;

use Angie\Events;
use InvalidArgumentException;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchPhrasePrefixQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use User;

final class ElasticSearchQueryResolver implements SearchQueryResolverInterface
{
    /**
     * @var string
     */
    private $term;

    /**
     * @var int
     */
    private $tenant_id;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array|null
     */
    private $filters;

    public function __construct($term, $tenant_id, User $user, array $filters = null)
    {
        if (!is_string($term) || empty($term)) {
            throw new InvalidArgumentException('Valid search term is required.');
        }

        if (!is_int($tenant_id) || empty($tenant_id)) {
            throw new InvalidArgumentException('Valid tenant id is required.');
        }

        if (!($user instanceof User)) {
            throw new InvalidArgumentException('Valid user is required.');
        }

        if (!is_array($filters) && $filters !== null) {
            throw new InvalidArgumentException('Valid search filters is required.');
        }

        $this->term = $term;
        $this->tenant_id = $tenant_id;
        $this->user = $user;
        $this->filters = $filters;
    }

    public function getQuery()
    {
        $query = new BoolQuery();

        if ($this->isPrefixSearch()) {
            // prepare prefix query
            $query->add($this->getPrefixSearchQuery());
        } else {
            // prepare multi match query
            $query->add($this->getMultiMatchQuery('cross_fields'));
        }

        // collect all user access search filter queries
        if (!$this->user->isOwner()) {
            $query->add($this->getUserAccessSearchQuery(), BoolQuery::FILTER);
        }

        // add tenant id must query
        $query->add($this->getTenantIdQuery(), BoolQuery::FILTER);

        // add criterions prepared from request
        if (is_foreachable($this->filters)) {
            foreach ($this->filters as $filter) {
                if ($filter instanceof BuilderInterface) {
                    $query->add($filter, BoolQuery::FILTER);
                }
            }
        }

        return $query->toArray();
    }

    /**
     * Check of term is for prefix or not.
     *
     * @return bool
     */
    public function isPrefixSearch()
    {
        // patern for modificators +,-,' and "
        // with comma for case where string is 'test,test' to be query string search
        $modificators_pattern = '/[-+"\'\,]/';
        // pattern to check if only one word is present
        $one_word_pattern = '/ /';

        if (!preg_match($one_word_pattern, $this->term) && preg_match($modificators_pattern, $this->term) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Return prefix query string query.
     *
     * @return BoolQuery
     */
    private function getPrefixSearchQuery()
    {
        $fields = [
          'name',
          'body',
          'body_extensions',
        ];

        $query = new BoolQuery();
        foreach ($fields as $field) {
            $query->add(new MatchPhrasePrefixQuery($field, $this->term), BoolQuery::SHOULD);
        }

        return $query;
    }

    /**
     * Return query string query.
     *
     * @return BoolQuery
     */
    private function getQueryStringQuery()
    {
        $params = [
            'fields' => [
                'name',
                'body',
                'body_extensions',
            ],
            'default_operator' => 'AND',
            'allow_leading_wildcard' => false,
            'analyze_wildcard' => true,
        ];

        $query = new BoolQuery();
        $query->add(new QueryStringQuery($this->term, $params));

        return $query;
    }

    /**
     * Return multi match query.
     *
     * @param  string    $type
     * @return BoolQuery
     */
    private function getMultiMatchQuery($type = 'best_fields')
    {
        $params = [
            'type' => $type,
            'operator' => 'AND',
        ];

        $fields = [
            'name',
            'body',
            'body_extensions',
        ];

        $query = new BoolQuery();
        $query->add(new MultiMatchQuery($fields, $this->term, $params));

        return $query;
    }

    /**
     * Return user access search query.
     *
     * @return BoolQuery
     */
    private function getUserAccessSearchQuery()
    {
        $query = new BoolQuery();

        Events::trigger('on_user_access_search_filter', [&$this->user, &$query]);

        return $query;
    }

    /**
     * Return tenant id query.
     *
     * @return BoolQuery
     */
    private function getTenantIdQuery()
    {
        return new TermQuery('tenant_id', $this->tenant_id);
    }
}
