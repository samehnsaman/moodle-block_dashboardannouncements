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

use block_dashboardannouncements\form\announcement_form;
use block_dashboardannouncements\local\announcement_manager;
use block_dashboardannouncements\local\audience_resolver;

/**
 * Create or edit an announcement.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$id = optional_param('id', 0, PARAM_INT);

require_login();

$systemcontext = context_system::instance();
require_capability('block/dashboardannouncements:manage', $systemcontext);

$manager = new announcement_manager();
$announcement = $id ? $manager->get_announcement($id, MUST_EXIST) : null;

$url = new moodle_url('/blocks/dashboardannouncements/edit.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($id ? get_string('editannouncement', 'block_dashboardannouncements') : get_string('createannouncement', 'block_dashboardannouncements'));
$PAGE->set_heading(get_string('manageannouncements', 'block_dashboardannouncements'));

$form = new announcement_form();

if ($announcement) {
    $resolver = new audience_resolver();
    $config = $resolver->decode_target_config($announcement->targetconfigjson);
    $announcement->categoryids = $config['categoryids'] ?? [];
    $announcement->cohortids = $config['cohortids'] ?? [];
    if (!empty($config['fieldsource']) && !empty($config['fieldkey'])) {
        $announcement->fieldlookup = $config['fieldsource'] . ':' . $config['fieldkey'];
    }
    $announcement->fieldoperator = $config['operator'] ?? audience_resolver::OP_EQUAL;
    $announcement->fieldmatchvalue = $config['matchvalue'] ?? '';
    $announcement->message = [
        'text' => $announcement->message,
        'format' => FORMAT_HTML,
    ];
    $announcement->attachment_filemanager = $manager->prepare_attachment_draft_itemid((int)$announcement->id);
    if (empty($announcement->timeend)) {
        $announcement->timeend = 0;
    }
    $form->set_data($announcement);
} else {
    $defaults = (object)[
        'attachment_filemanager' => $manager->prepare_attachment_draft_itemid(),
        'fieldoperator' => audience_resolver::OP_CONTAINS,
    ];
    $form->set_data($defaults);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/blocks/dashboardannouncements/manage.php'));
}

if ($data = $form->get_data()) {
    $savedid = $manager->save_announcement($data, (int)$USER->id);
    $manager->queue_first_delivery_if_required($savedid);
    redirect(
        new moodle_url('/blocks/dashboardannouncements/manage.php'),
        get_string('savesuccess', 'block_dashboardannouncements')
    );
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
