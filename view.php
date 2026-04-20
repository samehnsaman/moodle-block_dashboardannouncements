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
 * Display announcement listings and single announcement views.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$id = optional_param('id', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
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
if ($page > 0) {
    $pageparams['page'] = $page;
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

    $attachment = $manager->get_attachment_file((int)$announcement->id);
    $attachmentdisplay = presentation_helper::build_attachment_display(
        (int)$systemcontext->id,
        (int)$announcement->id,
        $attachment
    );
    $metaitems = presentation_helper::build_end_user_metadata_items(
        (int)($announcement->timestart ?? 0),
        $attachmentdisplay['name'],
        $postername
    );

    $detailcontent = html_writer::div(
        html_writer::tag(
            'h2',
            format_string($announcement->title),
            ['class' => 'dashboardannouncements-card__title']
        ),
        'dashboardannouncements-card__header'
    );
    $detailcontent .= presentation_helper::render_metadata_list($metaitems, 'dashboardannouncements-card__meta');
    $detailcontent .= html_writer::div(format_text($announcement->message, FORMAT_HTML), 'dashboardannouncements-card__body');
    if ($attachmentdisplay['actionhtml'] !== '') {
        $detailcontent .= html_writer::div($attachmentdisplay['actionhtml'], 'dashboardannouncements-card__actions');
    }

    echo html_writer::div($detailcontent, 'dashboardannouncements-card dashboardannouncements-detail');
    echo $OUTPUT->footer();
    exit;
}

$announcements = $manager->get_announcements_for_user((int)$USER->id);

if (!$announcements) {
    $emptystate = presentation_helper::get_empty_state_copy('default');
    echo presentation_helper::render_empty_state(
        $emptystate['title'],
        $emptystate['description']
    );
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_div('dashboardannouncements-stack');

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
    'class' => 'dashboardannouncements-filters mb-4',
]);
echo html_writer::start_div('d-flex flex-wrap align-items-end gap-2');
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('search', 'block_dashboardannouncements'), 'dashboardannouncements-search', false, ['class' => 'small text-muted mb-1 d-block']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'dashboardannouncements-search',
    'value' => $search,
    'placeholder' => get_string('search', 'block_dashboardannouncements'),
    'class' => 'form-control',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('datefrom', 'block_dashboardannouncements'), 'dashboardannouncements-datefrom', false, ['class' => 'small text-muted mb-1 d-block']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'datefrom',
    'id' => 'dashboardannouncements-datefrom',
    'value' => $datefrom,
    'class' => 'form-control',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::label(get_string('dateto', 'block_dashboardannouncements'), 'dashboardannouncements-dateto', false, ['class' => 'small text-muted mb-1 d-block']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'dateto',
    'id' => 'dashboardannouncements-dateto',
    'value' => $dateto,
    'class' => 'form-control',
]);
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $sort]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => $dir]);
echo html_writer::start_div('form-group mb-2 mr-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('filter', 'block_dashboardannouncements'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_div();
echo html_writer::start_div('form-group mb-2');
echo html_writer::link(
    new moodle_url('/blocks/dashboardannouncements/view.php'),
    get_string('resetfilters', 'block_dashboardannouncements'),
    ['class' => 'btn btn-secondary']
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

echo html_writer::div(
    html_writer::span(get_string('sort', 'moodle') . ': ', 'mr-2') .
    html_writer::div(
        $sortlink('date', get_string('date', 'block_dashboardannouncements')) . ' ' .
        $sortlink('title', get_string('titlepreview', 'block_dashboardannouncements')) . ' ' .
        $sortlink('attachment', get_string('attachment', 'block_dashboardannouncements')),
        'dashboardannouncements-cluster'
    ),
    'dashboardannouncements-toolbar'
);

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
    $itemurl = new moodle_url('/blocks/dashboardannouncements/view.php', ['id' => $announcement->id]);
    $attachment = $manager->get_attachment_file((int)$announcement->id);
    $hasattachment = (bool)$attachment;
    $titlepreview = shorten_text(trim(strip_tags(format_string($announcement->title))), 100, true, '');
    $messagepreview = presentation_helper::get_compact_message_preview((string)$announcement->message);
    $submitteddate = presentation_helper::format_metadata_datetime((int)($announcement->timecreated ?? 0));

    $cardcontent = html_writer::div(
        html_writer::tag(
            'h3',
            html_writer::link($itemurl, s($titlepreview)),
            ['class' => 'dashboardannouncements-card__title']
        ) . presentation_helper::render_attachment_indicator($hasattachment),
        'dashboardannouncements-card__header'
    );
    $cardcontent .= html_writer::div(
        s(get_string('submittedon', 'block_dashboardannouncements', $submitteddate)),
        'dashboardannouncements-card__meta dashboardannouncements-subtle'
    );
    $cardcontent .= html_writer::div(s($messagepreview), 'dashboardannouncements-card__body');

    $cardclasses = 'dashboardannouncements-card dashboardannouncements-card--compact';
    if (!empty($announcement->showaspopup)) {
        $cardclasses .= ' dashboardannouncements-card--popup';
    }
    $cards[] = html_writer::div($cardcontent, $cardclasses);
}

if (!$cards) {
    $emptystate = presentation_helper::get_empty_state_copy('list');
    echo presentation_helper::render_empty_state(
        $emptystate['title'],
        $emptystate['description']
    );
} else {
    echo html_writer::div(implode('', $cards), 'dashboardannouncements-list');

    if ($totalcount > $perpage) {
        $pagingparams = [
            'sort' => $sort,
            'dir' => $dir,
        ];
        if ($search !== '') {
            $pagingparams['search'] = $search;
        }
        if ($datefrom !== '') {
            $pagingparams['datefrom'] = $datefrom;
        }
        if ($dateto !== '') {
            $pagingparams['dateto'] = $dateto;
        }

        $baseurl = new moodle_url('/blocks/dashboardannouncements/view.php', $pagingparams);
        echo $OUTPUT->render(new paging_bar($totalcount, $page, $perpage, $baseurl, 'page'));
    }
}

echo html_writer::end_div();
echo $OUTPUT->footer();
