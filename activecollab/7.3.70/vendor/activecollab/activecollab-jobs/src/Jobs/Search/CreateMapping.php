<?php

/*
 * This file is part of the Active Collab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\ActiveCollabJobs\Jobs\Search;

use Elastica\Type\Mapping;
use InvalidArgumentException;

/**
 * @package ActiveCollab\ActiveCollabJobs\Jobs\Search
 */
class CreateMapping extends Job
{
    const FIELD_BOOLEAN = 'boolean';
    const FIELD_NUMERIC = 'numeric';
    const FIELD_DATE = 'date';
    const FIELD_DATETIME = 'datetime';
    const FIELD_STRING = 'string';
    const FIELD_TEXT = 'text';
    const FIELD_ATTACHMENTS = 'attachments';

    /**
     * Construct a new Job instance.
     *
     * @param  array|null               $data
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = null)
    {
        if (empty($data['type'])) {
            throw new InvalidArgumentException("'type' property is required");
        }

        if (empty($data['fields']) || !is_array($data['fields'])) {
            throw new InvalidArgumentException("'fields' property is required");
        }

        parent::__construct($data);
    }

    /**
     * Create mapping for the given type in the given index.
     */
    public function execute()
    {
        $index_name = $this->getData()['index'];
        $type = $this->getData()['type'];
        $fields = [];

        if ($this->mappingExists($index_name, $type)) {
            return;
        }

        $properties = [
            'name' => ['type' => 'string', 'include_in_all' => true],
            'name_sortable' => ['type' => 'string', 'index' => 'not_analyzed', 'include_in_all' => false],
            'name_suggestions' => ['type' => 'string', 'index_analyzer' => 'shingle_analyzer', 'include_in_all' => false],
            'extra_score' => ['type' => 'integer', 'include_in_all' => false],
            'url' => ['type' => 'string', 'include_in_all' => false],
        ];

        $attachment_fields = [];

        foreach ($fields as $field => $field_type) {
            switch ($field_type) {
                case self::FIELD_BOOLEAN:
                    $properties[$field] = ['type' => 'boolean', 'include_in_all' => false];
                    break;
                case self::FIELD_NUMERIC:
                    $properties[$field] = ['type' => 'integer', 'include_in_all' => false];
                    break;
                case self::FIELD_DATE:
                    $properties[$field] = ['index' => 'not_analyzed', 'type' => 'date', 'format' => 'yyyy-MM-dd', 'include_in_all' => false];
                    break;
                case self::FIELD_DATETIME:
                    $properties[$field] = ['index' => 'not_analyzed', 'type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss', 'include_in_all' => false];
                    break;
                case self::FIELD_STRING:
                case self::FIELD_TEXT:
                    $properties[$field] = ['type' => 'string', 'include_in_all' => true];
                    break;
                case self::FIELD_ATTACHMENTS:
                    $properties[$field] = ['type' => 'attachment', 'include_in_all' => true];
                    $attachment_fields[] = $field;
                    break;
                default:
                    throw new InvalidArgumentException("'$field_type' (used for '$field' field) is not a valid search index field type");
            }
        }

        $mapping = new Mapping($this->getType($index_name, $type));
        $mapping->setProperties($properties);

        if (count($attachment_fields)) {
            $mapping->setSource(['excludes' => $attachment_fields]);
        }

        $mapping->send();
    }
}
