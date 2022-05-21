<?php

/*
 * This file is part of the ActiveCollab project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

use Angie\HTML;

/**
 * Migrate code snippets to PRE tags.
 *
 * @package angie.migrations
 */
class MigrateCodeSnippetsToPre extends AngieModelMigration
{
    /**
     * Migrate up.
     */
    public function up()
    {
        if ($this->tableExists('code_snippets')) {
            $code_snippets = $this->useTables('code_snippets')[0];

            $code_snippets_map = [];

            if ($rows = $this->execute("SELECT id, syntax, body FROM $code_snippets")) {
                foreach ($rows as $row) {
                    $code_snippets_map[$row['id']] = $row;

                    if (empty($code_snippets_map[$row['id']]['syntax'])) {
                        $code_snippets_map[$row['id']]['syntax'] = 'plain';
                    }
                }

                foreach ($this->useTables('comments', 'discussions', 'tasks', 'text_documents') as $table) {
                    if ($rows_with_code = $this->execute("SELECT id, body FROM $table WHERE body LIKE ?", '%placeholder-type="code"%')) {
                        foreach ($rows_with_code as $row_with_code) {
                            $this->placeholderToPre($code_snippets_map, $table, $row_with_code['id'], $row_with_code['body']);
                        }
                    }
                }
            }

            $this->dropTable('code_snippets');
        }
    }

    /**
     * @param  string            $table
     * @param  int               $id
     * @param  string            $body
     * @throws InvalidParamError
     */
    public function placeholderToPre(array $map, $table, $id, $body)
    {
        $parser = HTML::getDOM(nl2br($body));

        if ($parser) {
            $code_snippet_placeholders = $parser->find('div[placeholder-type=code]');

            if (is_foreachable($code_snippet_placeholders)) {
                foreach ($code_snippet_placeholders as $code_snippet_placeholder) {
                    $code_snippet_id = array_var($code_snippet_placeholder->attr, 'placeholder-object-id', null);

                    if ($code_snippet_id && isset($map[$code_snippet_id])) {
                        $code_snippet_placeholder->outertext = '<pre data-syntax="' . $map[$code_snippet_id]['syntax'] . '">' . clean($map[$code_snippet_id]['body']) . '</pre>';
                    } else {
                        $code_snippet_placeholder->outertext = '<pre>Unknown snippet</pre>';
                    }
                }
            }

            $this->execute("UPDATE $table SET body = ? WHERE id = ?", (string) $parser, $id);
        }
    }
}
