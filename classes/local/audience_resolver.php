<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_dashboardannouncements\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves announcement audiences.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class audience_resolver {
    public const TARGET_ALL = 'all';
    public const TARGET_CATEGORY = 'category';
    public const TARGET_COHORT = 'cohort';
    public const TARGET_FIELD = 'field';

    public const FIELD_SOURCE_CORE = 'core';
    public const FIELD_SOURCE_PROFILE = 'profile';

    public const OP_CONTAINS = 'contains';
    public const OP_NOTCONTAINS = 'notcontains';
    public const OP_EQUAL = 'equal';
    public const OP_STARTSWITH = 'startswith';
    public const OP_ENDSWITH = 'endswith';
    public const OP_ISEMPTY = 'isempty';
    public const OP_ISNOTEMPTY = 'isnotempty';

    /**
     * Core user fields supported by the field selector.
     *
     * @return array<string, string>
     */
    protected function get_core_user_fields(): array {
        return [
            'username' => get_string('corefield:username', 'block_dashboardannouncements'),
            'firstname' => get_string('corefield:firstname', 'block_dashboardannouncements'),
            'lastname' => get_string('corefield:lastname', 'block_dashboardannouncements'),
            'email' => get_string('corefield:email', 'block_dashboardannouncements'),
            'idnumber' => get_string('corefield:idnumber', 'block_dashboardannouncements'),
            'institution' => get_string('corefield:institution', 'block_dashboardannouncements'),
            'department' => get_string('corefield:department', 'block_dashboardannouncements'),
            'address' => get_string('corefield:address', 'block_dashboardannouncements'),
            'city' => get_string('corefield:city', 'block_dashboardannouncements'),
            'country' => get_string('corefield:country', 'block_dashboardannouncements'),
            'phone1' => get_string('corefield:phone1', 'block_dashboardannouncements'),
            'phone2' => get_string('corefield:phone2', 'block_dashboardannouncements'),
            'url' => get_string('corefield:url', 'block_dashboardannouncements'),
        ];
    }

    /**
     * Return field options for the announcement form.
     *
     * @return array
     */
    public function get_field_options(): array {
        global $DB;

        $options = [];
        $coreprefix = get_string('fieldsource:core', 'block_dashboardannouncements') . ': ';
        $profileprefix = get_string('fieldsource:profile', 'block_dashboardannouncements') . ': ';

        foreach ($this->get_core_user_fields() as $key => $label) {
            $options['core:' . $key] = $coreprefix . $label;
        }

        $profilefields = $DB->get_records('user_info_field', null, 'name ASC', 'id, shortname, name');
        foreach ($profilefields as $field) {
            $options['profile:' . $field->shortname] = $profileprefix . $field->name;
        }

        return $options;
    }

    /**
     * Parse form field lookup value.
     *
     * @param string $lookup
     * @return array{0:string,1:string}
     */
    public function parse_field_lookup(string $lookup): array {
        if (strpos($lookup, ':') === false) {
            return ['', ''];
        }

        [$source, $key] = explode(':', $lookup, 2);
        return [$source, $key];
    }

    /**
     * Resolve display label for a selected field.
     *
     * @param string $source
     * @param string $key
     * @return string
     */
    public function get_field_label(string $source, string $key): string {
        global $DB;

        if ($source === self::FIELD_SOURCE_CORE) {
            $fields = $this->get_core_user_fields();
            return $fields[$key] ?? $key;
        }

        if ($source === self::FIELD_SOURCE_PROFILE && $key !== '') {
            $field = $DB->get_record('user_info_field', ['shortname' => $key], 'name', IGNORE_MISSING);
            if ($field) {
                return $field->name;
            }
        }

        return $key;
    }

    /**
     * Decode stored configuration.
     *
     * @param string|null $json
     * @return array
     */
    public function decode_target_config(?string $json): array {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode target configuration.
     *
     * @param array $config
     * @return string
     */
    public function encode_target_config(array $config): string {
        return json_encode($config);
    }

    /**
     * Validate target configuration.
     *
     * @param string $targettype
     * @param array $config
     * @return bool
     */
    public function is_valid_target_config(string $targettype, array $config): bool {
        switch ($targettype) {
            case self::TARGET_ALL:
                return true;

            case self::TARGET_CATEGORY:
                return !empty($config['categoryids']) && is_array($config['categoryids']);

            case self::TARGET_COHORT:
                return !empty($config['cohortids']) && is_array($config['cohortids']);

            case self::TARGET_FIELD:
                return !empty($config['fieldsource']) &&
                    !empty($config['fieldkey']) &&
                    !empty($config['operator']) &&
                    array_key_exists('matchvalue', $config);

            default:
                return false;
        }
    }

    /**
     * Get users matched by announcement targeting.
     *
     * @param \stdClass $announcement
     * @return array<int, \stdClass>
     */
    public function get_target_users(\stdClass $announcement): array {
        global $DB;

        $config = $this->decode_target_config($announcement->targetconfigjson ?? null);

        switch ($announcement->targettype) {
            case self::TARGET_ALL:
                return $DB->get_records_select(
                    'user',
                    'deleted = 0 AND suspended = 0 AND confirmed = 1',
                    [],
                    '',
                    'id, firstname, lastname, email'
                );

            case self::TARGET_CATEGORY:
                return $this->get_category_users($config);

            case self::TARGET_COHORT:
                return $this->get_cohort_users($config);

            case self::TARGET_FIELD:
                return $this->get_field_users($config);
        }

        return [];
    }

    /**
     * Check current user match against announcement.
     *
     * @param \stdClass $announcement
     * @param int $userid
     * @return bool
     */
    public function user_matches(\stdClass $announcement, int $userid): bool {
        $users = $this->get_target_users($announcement);
        return array_key_exists($userid, $users);
    }

    /**
     * Build admin-only audience summary.
     *
     * @param \stdClass $announcement
     * @return string
     */
    public function get_audience_summary(\stdClass $announcement): string {
        global $DB;

        $config = $this->decode_target_config($announcement->targetconfigjson ?? null);

        switch ($announcement->targettype) {
            case self::TARGET_ALL:
                return get_string('targettype:all', 'block_dashboardannouncements');

            case self::TARGET_CATEGORY:
                if (empty($config['categoryids'])) {
                    return get_string('targettype:category', 'block_dashboardannouncements');
                }
                $names = [];
                list($insql, $params) = $DB->get_in_or_equal($config['categoryids'], SQL_PARAMS_NAMED);
                $records = $DB->get_records_select('course_categories', 'id ' . $insql, $params, '', 'id, name');
                foreach ($records as $record) {
                    $names[] = format_string($record->name);
                }
                return get_string('targettype:category', 'block_dashboardannouncements') . ': ' . implode(', ', $names);

            case self::TARGET_COHORT:
                if (empty($config['cohortids'])) {
                    return get_string('targettype:cohort', 'block_dashboardannouncements');
                }
                $names = [];
                list($insql, $params) = $DB->get_in_or_equal($config['cohortids'], SQL_PARAMS_NAMED);
                $records = $DB->get_records_select('cohort', 'id ' . $insql, $params, '', 'id, name');
                foreach ($records as $record) {
                    $names[] = format_string($record->name);
                }
                return get_string('targettype:cohort', 'block_dashboardannouncements') . ': ' . implode(', ', $names);

            case self::TARGET_FIELD:
                $operator = $config['operator'] ?? self::OP_EQUAL;
                $operatorlabel = get_string('fieldoperator:' . $operator, 'block_dashboardannouncements');
                $summary = get_string('targettype:field', 'block_dashboardannouncements') . ': ' .
                    s(($config['fieldlabel'] ?? $config['fieldkey'] ?? '')) . ' ' . $operatorlabel;
                if (!$this->operator_uses_value($operator)) {
                    return $summary;
                }
                return $summary . ' ' . s(($config['matchvalue'] ?? ''));
        }

        return '';
    }

    /**
     * Get category users through active enrolments.
     *
     * @param array $config
     * @return array<int, \stdClass>
     */
    protected function get_category_users(array $config): array {
        global $DB;

        if (empty($config['categoryids'])) {
            return [];
        }

        $clauses = [];
        $params = [];
        foreach (array_values($config['categoryids']) as $index => $categoryid) {
            $category = $DB->get_record('course_categories', ['id' => (int)$categoryid], 'id, path', IGNORE_MISSING);
            if (!$category) {
                continue;
            }

            $idparam = 'catid' . $index;
            $pathparam = 'catpath' . $index;
            $clauses[] = '(cc.id = :' . $idparam . ' OR ' . $DB->sql_like('cc.path', ':' . $pathparam, false, false) . ')';
            $params[$idparam] = (int)$category->id;
            $params[$pathparam] = $category->path . '/%';
        }

        if (!$clauses) {
            return [];
        }

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND u.confirmed = 1
                   AND ue.status = 0
                   AND e.status = 0
                   AND (" . implode(' OR ', $clauses) . ")";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get cohort users.
     *
     * @param array $config
     * @return array<int, \stdClass>
     */
    protected function get_cohort_users(array $config): array {
        global $DB;

        if (empty($config['cohortids'])) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($config['cohortids'], SQL_PARAMS_NAMED, 'coh');
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                  FROM {user} u
                  JOIN {cohort_members} cm ON cm.userid = u.id
                 WHERE cm.cohortid $insql
                   AND u.deleted = 0
                   AND u.suspended = 0
                   AND u.confirmed = 1";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get field-matched users.
     *
     * @param array $config
     * @return array<int, \stdClass>
     */
    protected function get_field_users(array $config): array {
        global $DB;

        if (empty($config['fieldsource']) || empty($config['fieldkey'])) {
            return [];
        }

        $operator = (string)($config['operator'] ?? self::OP_EQUAL);
        $matchvalue = (string)($config['matchvalue'] ?? '');

        if ($config['fieldsource'] === self::FIELD_SOURCE_CORE) {
            $corefields = $this->get_core_user_fields();
            if (!array_key_exists($config['fieldkey'], $corefields)) {
                return [];
            }

            [$condition, $params] = $this->build_text_condition('u.' . $config['fieldkey'], $operator, $matchvalue);
            $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                      FROM {user} u
                     WHERE u.deleted = 0
                       AND u.suspended = 0
                       AND u.confirmed = 1
                       AND {$condition}";

            return $DB->get_records_sql($sql, $params);
        }

        $fieldparams = ['shortname' => $config['fieldkey']];
        $field = $DB->get_record('user_info_field', $fieldparams, 'id, shortname', IGNORE_MISSING);
        if (!$field) {
            return [];
        }

        [$condition, $params] = $this->build_text_condition('uid.data', $operator, $matchvalue);
        $params['fieldid'] = $field->id;

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                  FROM {user} u
             LEFT JOIN {user_info_data} uid
                    ON uid.userid = u.id
                   AND uid.fieldid = :fieldid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND u.confirmed = 1
                   AND {$condition}";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Build SQL for text-based field operators.
     *
     * @param string $column
     * @param string $operator
     * @param string $value
     * @return array{0:string,1:array}
     */
    protected function build_text_condition(string $column, string $operator, string $value): array {
        global $DB;

        switch ($operator) {
            case self::OP_CONTAINS:
                return [$DB->sql_like($column, ':matchvalue', false, false), ['matchvalue' => '%' . $value . '%']];

            case self::OP_NOTCONTAINS:
                return [
                    '(' . $column . ' IS NULL OR NOT (' . $DB->sql_like($column, ':matchvalue', false, false) . '))',
                    ['matchvalue' => '%' . $value . '%'],
                ];

            case self::OP_STARTSWITH:
                return [$DB->sql_like($column, ':matchvalue', false, false), ['matchvalue' => $value . '%']];

            case self::OP_ENDSWITH:
                return [$DB->sql_like($column, ':matchvalue', false, false), ['matchvalue' => '%' . $value]];

            case self::OP_ISEMPTY:
                return ["({$column} IS NULL OR {$column} = '')", []];

            case self::OP_ISNOTEMPTY:
                return ["({$column} IS NOT NULL AND {$column} <> '')", []];

            case self::OP_EQUAL:
            default:
                return ["{$column} = :matchvalue", ['matchvalue' => $value]];
        }
    }

    /**
     * Whether the selected operator needs a value.
     *
     * @param string $operator
     * @return bool
     */
    public function operator_uses_value(string $operator): bool {
        return !in_array($operator, [self::OP_ISEMPTY, self::OP_ISNOTEMPTY], true);
    }
}
