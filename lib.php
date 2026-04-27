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

require_once(__DIR__ . '/classes/local/announcement_manager.php');

/**
 * Serve block attachments through pluginfile.php.
 *
 * @param stdClass $course
 * @param stdClass $birecordorcm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return void
 */
function block_dashboardannouncements_pluginfile(
    $course,
    $birecordorcm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): void {
    global $USER;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== \block_dashboardannouncements\local\announcement_manager::ATTACHMENT_FILEAREA) {
        send_file_not_found();
    }

    require_login();

    $announcementid = (int)array_shift($args);
    $filename = (string)array_pop($args);
    $manager = new \block_dashboardannouncements\local\announcement_manager();
    $announcement = $manager->get_announcement($announcementid, IGNORE_MISSING);
    $canmanage = has_capability('block/dashboardannouncements:manage', $context);

    if (!$announcement || (!$canmanage && !$manager->user_can_view_announcement($announcement, (int)$USER->id))) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        'block_dashboardannouncements',
        $filearea,
        $announcementid,
        '/',
        $filename
    );

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, true, $options);
}
