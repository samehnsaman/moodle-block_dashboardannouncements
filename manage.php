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

require_once(__DIR__ . '/../../config.php');

use block_dashboardannouncements\local\announcement_manager;

/**
 * Manage announcements list.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$archiveid = optional_param('archive', 0, PARAM_INT);

require_login();

$systemcontext = context_system::instance();
require_capability('block/dashboardannouncements:manage', $systemcontext);

$manager = new announcement_manager();

if ($archiveid && confirm_sesskey()) {
    $manager->archive_announcement($archiveid, (int)$USER->id);
    redirect(new moodle_url('/blocks/dashboardannouncements/manage.php'), get_string('archivedsuccess', 'block_dashboardannouncements'));
}

$PAGE->set_url(new moodle_url('/blocks/dashboardannouncements/manage.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('manageannouncements', 'block_dashboardannouncements'));
$PAGE->set_heading(get_string('manageannouncements', 'block_dashboardannouncements'));

$announcements = $manager->get_all_announcements_for_management();

echo $OUTPUT->header();
echo $OUTPUT->single_button(
    new moodle_url('/blocks/dashboardannouncements/edit.php'),
    get_string('createannouncement', 'block_dashboardannouncements')
);

$table = new html_table();
$table->head = [
    get_string('title', 'block_dashboardannouncements'),
    get_string('status', 'block_dashboardannouncements'),
    get_string('audiencesummary', 'block_dashboardannouncements'),
    get_string('targetedcount', 'block_dashboardannouncements'),
    get_string('notifiedcount', 'block_dashboardannouncements'),
    get_string('actions', 'block_dashboardannouncements'),
];
$table->data = [];

foreach ($announcements as $announcement) {
    $editurl = new moodle_url('/blocks/dashboardannouncements/edit.php', ['id' => $announcement->id]);
    $archiveurl = new moodle_url('/blocks/dashboardannouncements/manage.php', [
        'archive' => $announcement->id,
        'sesskey' => sesskey(),
    ]);

    $targeted = $announcement->targetedcountsnapshot === null
        ? get_string('targetedblank', 'block_dashboardannouncements')
        : $announcement->targetedcountsnapshot;

    $actions = html_writer::link($editurl, get_string('edit', 'block_dashboardannouncements')) . ' | ' .
        html_writer::link($archiveurl, get_string('archive', 'block_dashboardannouncements'));

    $table->data[] = [
        format_string($announcement->title),
        s($announcement->status),
        s($announcement->audiencesummary),
        s((string)$targeted),
        s((string)$announcement->notifieduniquecount),
        $actions,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
