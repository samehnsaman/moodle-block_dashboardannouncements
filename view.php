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
 * Display announcement listings and single announcement views.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$id = optional_param('id', 0, PARAM_INT);
$search = trim(optional_param('search', '', PARAM_TEXT));
$datefrom = optional_param('datefrom', '', PARAM_RAW_TRIMMED);
$dateto = optional_param('dateto', '', PARAM_RAW_TRIMMED);
$sort = optional_param('sort', 'date', PARAM_ALPHA);
$dir = strtoupper(optional_param('dir', 'DESC', PARAM_ALPHA));

require_login();

$systemcontext = context_system::instance();
require_capability('block/dashboardannouncements:viewall', $systemcontext);

$pageparams = ['id' => $id];
if ($search !== '') {
    $pageparams['search'] = $search;
}
if ($datefrom !== '') {
    $pageparams['datefrom'] = $datefrom;
}
if ($dateto !== '') {
    $pageparams['dateto'] = $dateto;
}
if ($sort !== 'date' || $dir !== 'DESC') {
    $pageparams['sort'] = $sort;
    $pageparams['dir'] = $dir;
}
$PAGE->set_url(new moodle_url('/blocks/dashboardannouncements/view.php', $pageparams));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('announcements', 'block_dashboardannouncements'));
$PAGE->set_heading(get_string('announcements', 'block_dashboardannouncements'));

$manager = new announcement_manager();

echo $OUTPUT->header();

if ($id) {
    $announcement = $manager->get_announcement($id, MUST_EXIST);
    $canmanage = has_capability('block/dashboardannouncements:manage', $systemcontext);
    if (!$canmanage && !$manager->user_can_view_announcement($announcement, (int)$USER->id)) {
        throw new required_capability_exception($systemcontext, 'block/dashboardannouncements:viewall', 'nopermissions', '');
    }

    $postername = '-';
    if (!empty($announcement->createdby)) {
        $poster = $DB->get_record('user', ['id' => (int)$announcement->createdby], 'id, firstname, lastname', IGNORE_MISSING);
        if ($poster) {
            $postername = fullname($poster);
        }
    }

    $datevalue = !empty($announcement->timestart)
        ? userdate((int)$announcement->timestart, get_string('strftimedatetime', 'langconfig'))
        : get_string('noattachment', 'block_dashboardannouncements');

    $attachmentcell = get_string('noattachment', 'block_dashboardannouncements');
    echo $OUTPUT->heading(format_string($announcement->title), 2);
    $attachment = $manager->get_attachment_file((int)$announcement->id);
    if ($attachment) {
        $attachmenturl = moodle_url::make_pluginfile_url(
            $systemcontext->id,
            'block_dashboardannouncements',
            announcement_manager::ATTACHMENT_FILEAREA,
            $announcement->id,
            '/',
            $attachment->get_filename()
        );
        $attachmentcell = html_writer::link($attachmenturl, s($attachment->get_filename()));
    }

    $detailtable = new html_table();
    $detailtable->attributes['class'] = 'generaltable dashboardannouncements-detailtable';
    $detailtable->data = [
        [get_string('date', 'block_dashboardannouncements'), s($datevalue)],
        [get_string('postedby', 'block_dashboardannouncements'), s($postername)],
        [get_string('attachment', 'block_dashboardannouncements'), $attachmentcell],
    ];

    echo html_writer::table($detailtable);
    echo html_writer::div(format_text($announcement->message, FORMAT_HTML), 'dashboardannouncement-message');
    echo $OUTPUT->footer();
    exit;
}

$announcements = $manager->get_announcements_for_user((int)$USER->id);

if (!$announcements) {
    echo html_writer::div(get_string('noannouncements', 'block_dashboardannouncements'));
    echo $OUTPUT->footer();
    exit;
}

$datefromts = $datefrom !== '' ? strtotime($datefrom . ' 00:00:00') : null;
$datetots = $dateto !== '' ? strtotime($dateto . ' 23:59:59') : null;
$filtered = [];

foreach ($announcements as $announcement) {
    $titletext = trim(strip_tags(format_string($announcement->title)));
    $messagetext = trim(strip_tags(format_text($announcement->message, FORMAT_HTML)));

    if ($search !== '') {
        $haystack = core_text::strtolower($titletext . ' ' . $messagetext);
        if (core_text::strpos($haystack, core_text::strtolower($search)) === false) {
            continue;
        }
    }

    $announcementdate = !empty($announcement->timestart) ? (int)$announcement->timestart : 0;
    if ($datefromts !== null && $announcementdate < $datefromts) {
        continue;
    }
    if ($datetots !== null && $announcementdate > $datetots) {
        continue;
    }

    $filtered[] = $announcement;
}

$sort = in_array($sort, ['title', 'date', 'attachment'], true) ? $sort : 'date';
$dir = $dir === 'ASC' ? 'ASC' : 'DESC';

usort($filtered, static function($leftannouncement, $rightannouncement) use ($manager, $sort, $dir) {
    switch ($sort) {
        case 'title':
            $left = core_text::strtolower(trim(strip_tags(format_string($leftannouncement->title))));
            $right = core_text::strtolower(trim(strip_tags(format_string($rightannouncement->title))));
            break;
        case 'attachment':
            $leftfile = $manager->get_attachment_file((int)$leftannouncement->id);
            $rightfile = $manager->get_attachment_file((int)$rightannouncement->id);
            $left = $leftfile ? core_text::strtolower($leftfile->get_filename()) : '';
            $right = $rightfile ? core_text::strtolower($rightfile->get_filename()) : '';
            break;
        case 'date':
        default:
            $left = !empty($leftannouncement->timestart) ? (int)$leftannouncement->timestart : 0;
            $right = !empty($rightannouncement->timestart) ? (int)$rightannouncement->timestart : 0;
            break;
    }

    if ($left === $right) {
        return 0;
    }

    $result = $left <=> $right;
    return $dir === 'ASC' ? $result : -$result;
});

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url('/blocks/dashboardannouncements/view.php'),
    'class' => 'dashboardannouncements-filters',
]);
echo html_writer::start_div('dashboardannouncements-filters__grid');
echo html_writer::start_div('dashboardannouncements-filters__group');
echo html_writer::label(get_string('search', 'block_dashboardannouncements'), 'dashboardannouncements-search', false, ['class' => 'dashboardannouncements-filters__label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'dashboardannouncements-search',
    'value' => $search,
    'placeholder' => get_string('search', 'block_dashboardannouncements'),
    'class' => 'dashboardannouncements-filters__input',
]);
echo html_writer::end_div();
echo html_writer::start_div('dashboardannouncements-filters__group');
echo html_writer::label(get_string('datefrom', 'block_dashboardannouncements'), 'dashboardannouncements-datefrom', false, ['class' => 'dashboardannouncements-filters__label']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'datefrom',
    'id' => 'dashboardannouncements-datefrom',
    'value' => $datefrom,
    'class' => 'dashboardannouncements-filters__input',
]);
echo html_writer::end_div();
echo html_writer::start_div('dashboardannouncements-filters__group');
echo html_writer::label(get_string('dateto', 'block_dashboardannouncements'), 'dashboardannouncements-dateto', false, ['class' => 'dashboardannouncements-filters__label']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'dateto',
    'id' => 'dashboardannouncements-dateto',
    'value' => $dateto,
    'class' => 'dashboardannouncements-filters__input',
]);
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $sort]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => $dir]);
echo html_writer::start_div('dashboardannouncements-filters__actions');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('filter', 'block_dashboardannouncements'),
    'class' => 'dashboardannouncements-filters__submit',
]);
echo html_writer::link(
    new moodle_url('/blocks/dashboardannouncements/view.php'),
    get_string('resetfilters', 'block_dashboardannouncements'),
    ['class' => 'dashboardannouncements-filters__reset']
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

$sortlink = static function(string $column, string $label) use ($search, $datefrom, $dateto, $sort, $dir): string {
    $nextdir = ($column === $sort && $dir === 'ASC') ? 'DESC' : 'ASC';
    $params = ['sort' => $column, 'dir' => $nextdir];
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($datefrom !== '') {
        $params['datefrom'] = $datefrom;
    }
    if ($dateto !== '') {
        $params['dateto'] = $dateto;
    }
    $indicator = $column === $sort ? ($dir === 'ASC' ? ' ▲' : ' ▼') : '';
    return html_writer::link(new moodle_url('/blocks/dashboardannouncements/view.php', $params), $label . $indicator);
};

$table = new html_table();
$table->attributes['class'] = 'generaltable dashboardannouncements-table';
$table->head = [
    $sortlink('title', get_string('titlepreview', 'block_dashboardannouncements')),
    get_string('messagepreview', 'block_dashboardannouncements'),
    $sortlink('date', get_string('date', 'block_dashboardannouncements')),
    $sortlink('attachment', get_string('attachment', 'block_dashboardannouncements')),
];
$table->data = [];

foreach ($filtered as $announcement) {
    $itemurl = new moodle_url('/blocks/dashboardannouncements/view.php', ['id' => $announcement->id]);
    $attachment = $manager->get_attachment_file((int)$announcement->id);
    $attachmentcell = get_string('noattachment', 'block_dashboardannouncements');

    if ($attachment) {
        $attachmenturl = moodle_url::make_pluginfile_url(
            $systemcontext->id,
            'block_dashboardannouncements',
            announcement_manager::ATTACHMENT_FILEAREA,
            $announcement->id,
            '/',
            $attachment->get_filename()
        );
        $attachmentcell = html_writer::link($attachmenturl, s($attachment->get_filename()));
    }

    $datevalue = !empty($announcement->timestart)
        ? userdate((int)$announcement->timestart, get_string('strftimedatetime', 'langconfig'))
        : get_string('noattachment', 'block_dashboardannouncements');
    $titlepreview = shorten_text(trim(strip_tags(format_string($announcement->title))), 100, true, '');
    $messagepreview = shorten_text(trim(strip_tags(format_text($announcement->message, FORMAT_HTML))), 140, true, '');

    $table->data[] = [
        html_writer::link($itemurl, s($titlepreview)),
        s($messagepreview),
        s($datevalue),
        $attachmentcell,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
