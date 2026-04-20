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
use block_dashboardannouncements\local\presentation_helper;

/**
 * Manage announcements list.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$archiveid = optional_param('archive', 0, PARAM_INT);
$search = trim(optional_param('search', '', PARAM_TEXT));
$statusfilter = optional_param('statusfilter', '', PARAM_ALPHA);
$datefrom = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto = optional_param('dateto', '', PARAM_RAW_TRIMMED);
$page = optional_param('page', 0, PARAM_INT);

require_login();

$systemcontext = context_system::instance();
require_capability('block/dashboardannouncements:manage', $systemcontext);

$manager = new announcement_manager();
$baseparams = [];
if ($search !== '') {
    $baseparams['search'] = $search;
}
if ($statusfilter !== '') {
    $baseparams['statusfilter'] = $statusfilter;
}
if ($datefrom !== '') {
    $baseparams['datefrom'] = $datefrom;
}
if ($dateto !== '') {
    $baseparams['dateto'] = $dateto;
}
if ($page > 0) {
    $baseparams['page'] = $page;
}

if ($archiveid && confirm_sesskey()) {
    $manager->archive_announcement($archiveid, (int)$USER->id);
    redirect(new moodle_url('/blocks/dashboardannouncements/manage.php', $baseparams), get_string('archivedsuccess', 'block_dashboardannouncements'));
}

$PAGE->set_url(new moodle_url('/blocks/dashboardannouncements/manage.php', $baseparams));
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

$datefromts = $datefrom !== '' ? strtotime($datefrom . ' 00:00:00') : null;
$datetots = $dateto !== '' ? strtotime($dateto . ' 23:59:59') : null;
$knownstatuses = ['draft', 'published', 'disabled', 'archived'];
$statusfilter = in_array($statusfilter, $knownstatuses, true) ? $statusfilter : '';
$filtered = [];

foreach ($announcements as $announcement) {
    $titletext = trim(strip_tags(format_string($announcement->title)));
    $audiencetext = trim((string)$announcement->audiencesummary);

    if ($search !== '') {
        $haystack = core_text::strtolower($titletext . ' ' . $audiencetext);
        if (core_text::strpos($haystack, core_text::strtolower($search)) === false) {
            continue;
        }
    }

    if ($statusfilter !== '' && (string)$announcement->status !== $statusfilter) {
        continue;
    }

    $announcementdate = !empty($announcement->timecreated) ? (int)$announcement->timecreated : 0;
    if ($datefromts !== null && $announcementdate < $datefromts) {
        continue;
    }
    if ($datetots !== null && $announcementdate > $datetots) {
        continue;
    }

    $filtered[] = $announcement;
}

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url('/blocks/dashboardannouncements/manage.php'),
    'class' => 'dashboardannouncements-filters mb-4',
]);
echo html_writer::start_div('d-flex flex-wrap align-items-end gap-2');
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('search', 'block_dashboardannouncements'), 'dashboardannouncements-manage-search', false, ['class' => 'small text-muted mb-1 d-block']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'dashboardannouncements-manage-search',
    'value' => $search,
    'placeholder' => get_string('search', 'block_dashboardannouncements'),
    'class' => 'form-control',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('status', 'block_dashboardannouncements'), 'dashboardannouncements-manage-status', false, ['class' => 'small text-muted mb-1 d-block']);
$statusoptions = [
    '' => get_string('all', 'moodle'),
    'draft' => get_string('status:draft', 'block_dashboardannouncements'),
    'published' => get_string('status:published', 'block_dashboardannouncements'),
    'disabled' => get_string('status:disabled', 'block_dashboardannouncements'),
    'archived' => get_string('status:archived', 'block_dashboardannouncements'),
];
echo html_writer::select(
    $statusoptions,
    'statusfilter',
    $statusfilter,
    false,
    ['id' => 'dashboardannouncements-manage-status', 'class' => 'custom-select']
);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('datefrom', 'block_dashboardannouncements'), 'dashboardannouncements-manage-datefrom', false, ['class' => 'small text-muted mb-1 d-block']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'datefrom',
    'id' => 'dashboardannouncements-manage-datefrom',
    'value' => $datefrom,
    'class' => 'form-control',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('dateto', 'block_dashboardannouncements'), 'dashboardannouncements-manage-dateto', false, ['class' => 'small text-muted mb-1 d-block']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'dateto',
    'id' => 'dashboardannouncements-manage-dateto',
    'value' => $dateto,
    'class' => 'form-control',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('filter', 'block_dashboardannouncements'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2');
echo html_writer::link(
    new moodle_url('/blocks/dashboardannouncements/manage.php'),
    get_string('resetfilters', 'block_dashboardannouncements'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

if (!$filtered) {
    $emptystate = presentation_helper::get_empty_state_copy('manage');
    echo presentation_helper::render_empty_state(
        $emptystate['title'],
        $emptystate['description']
    );
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('dashboardannouncements-stack');

$cards = [];
$perpage = 10;
$totalcount = count($filtered);
$page = max(0, $page);
$offset = $page * $perpage;
if ($totalcount > 0 && $offset >= $totalcount) {
    $page = (int)floor(($totalcount - 1) / $perpage);
    $offset = $page * $perpage;
}
$pagedannouncements = array_slice($filtered, $offset, $perpage);

foreach ($pagedannouncements as $announcement) {
    $editurl = new moodle_url('/blocks/dashboardannouncements/edit.php', ['id' => $announcement->id]);
    $archiveurl = new moodle_url('/blocks/dashboardannouncements/manage.php', [
        'archive' => $announcement->id,
        'sesskey' => sesskey(),
        'search' => $search,
        'statusfilter' => $statusfilter,
        'datefrom' => $datefrom,
        'dateto' => $dateto,
        'page' => $page,
    ]);

    $targeted = $announcement->targetedcountsnapshot === null
        ? get_string('targetedblank', 'block_dashboardannouncements')
        : $announcement->targetedcountsnapshot;

    $title = format_string($announcement->title);
    $statusbadge = presentation_helper::render_status_badge((string)$announcement->status);
    $audiencesummary = presentation_helper::format_metadata_value((string)$announcement->audiencesummary, 'metadatanotavailable');
    $metaitems = presentation_helper::build_manager_metadata_items(
        $audiencesummary,
        (string)$targeted,
        (string)$announcement->notifieduniquecount
    );
    $metaitems[] = [
        'label' => get_string('date', 'block_dashboardannouncements'),
        'value' => presentation_helper::format_metadata_datetime((int)($announcement->timecreated ?? 0)),
    ];
    $actions = presentation_helper::render_action_links([
        [
            'url' => $editurl,
            'label' => get_string('edit', 'block_dashboardannouncements'),
        ],
        [
            'url' => $archiveurl,
            'label' => get_string('archive', 'block_dashboardannouncements'),
        ],
    ]);

    $cardcontent = html_writer::div(
        html_writer::tag('h3', $title, ['class' => 'dashboardannouncements-card__title']) . $statusbadge,
        'dashboardannouncements-card__header'
    );
    $cardcontent .= presentation_helper::render_metadata_list($metaitems, 'dashboardannouncements-card__meta');
    $cardcontent .= html_writer::div(
        html_writer::div(
            html_writer::tag('p', get_string('targetedcount', 'block_dashboardannouncements'), ['class' => 'dashboardannouncements-stat__label']) .
            html_writer::tag('p', s((string)$targeted), ['class' => 'dashboardannouncements-stat__value']),
            'dashboardannouncements-stat'
        ) .
        html_writer::div(
            html_writer::tag('p', get_string('notifiedcount', 'block_dashboardannouncements'), ['class' => 'dashboardannouncements-stat__label']) .
            html_writer::tag('p', s((string)$announcement->notifieduniquecount), ['class' => 'dashboardannouncements-stat__value']),
            'dashboardannouncements-stat'
        ),
        'dashboardannouncements-stat-grid'
    );
    $cardcontent .= html_writer::div($actions, 'dashboardannouncements-card__actions');

    $cards[] = html_writer::div($cardcontent, 'dashboardannouncements-card');
}

echo html_writer::div(implode('', $cards), 'dashboardannouncements-manage');
if ($totalcount > $perpage) {
    $pagingparams = [];
    if ($search !== '') {
        $pagingparams['search'] = $search;
    }
    if ($statusfilter !== '') {
        $pagingparams['statusfilter'] = $statusfilter;
    }
    if ($datefrom !== '') {
        $pagingparams['datefrom'] = $datefrom;
    }
    if ($dateto !== '') {
        $pagingparams['dateto'] = $dateto;
    }

    $baseurl = new moodle_url('/blocks/dashboardannouncements/manage.php', $pagingparams);
    echo $OUTPUT->render(new paging_bar($totalcount, $page, $perpage, $baseurl, 'page'));
}
echo html_writer::end_div();
echo $OUTPUT->footer();
