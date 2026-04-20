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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for dashboard announcements.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_block_dashboardannouncements_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026041901) {
        $DB->execute(
            "UPDATE {block_dashboardannouncements}
                SET sendmode = :message
              WHERE sendmode IN (:notification, :email, :both)",
            [
                'message' => 'message',
                'notification' => 'notification',
                'email' => 'email',
                'both' => 'both',
            ]
        );

        $DB->execute(
            "UPDATE {block_dashann_delqueue}
                SET channel = :message
              WHERE channel IN (:notification, :email, :both)",
            [
                'message' => 'message',
                'notification' => 'notification',
                'email' => 'email',
                'both' => 'both',
            ]
        );

        $DB->execute(
            "UPDATE {block_dashann_dellog}
                SET channel = :message
              WHERE channel IN (:notification, :email, :both)",
            [
                'message' => 'message',
                'notification' => 'notification',
                'email' => 'email',
                'both' => 'both',
            ]
        );

        $records = $DB->get_records('block_dashboardannouncements', ['targettype' => 'profilefield']);
        foreach ($records as $record) {
            $config = json_decode($record->targetconfigjson ?? '', true);
            if (!is_array($config)) {
                $config = [];
            }

            $record->targettype = 'field';
            $record->targetconfigjson = json_encode([
                'fieldsource' => 'profile',
                'fieldkey' => (string)($config['fieldshortname'] ?? ''),
                'fieldlabel' => (string)($config['fieldshortname'] ?? ''),
                'operator' => (string)($config['operator'] ?? 'equal'),
                'matchvalue' => (string)($config['matchvalue'] ?? ''),
            ]);
            $DB->update_record('block_dashboardannouncements', $record);
        }

        upgrade_block_savepoint(true, 2026041901, 'dashboardannouncements');
    }

    if ($oldversion < 2026041904) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('block_dashboardannouncements');
        $field = new xmldb_field('showaspopup', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sendmode');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2026041904, 'dashboardannouncements');
    }

    return true;
}
