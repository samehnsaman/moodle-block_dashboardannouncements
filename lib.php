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

/**
 * Inject popup announcement markup before the page body for logged-in users.
 *
 * @return string
 */
function block_dashboardannouncements_before_standard_top_of_body_html(): string {
    global $USER;

    if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
        return '';
    }

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $systemcontext = \context_system::instance();
    if (
        !has_capability('block/dashboardannouncements:view', $systemcontext) ||
        !has_capability('block/dashboardannouncements:viewall', $systemcontext)
    ) {
        return '';
    }

    $manager = new \block_dashboardannouncements\local\announcement_manager();
    $announcement = $manager->get_latest_popup_announcement_for_user((int)$USER->id);
    if (!$announcement) {
        return '';
    }

    $manager->mark_user_seen_popup_announcement((int)$USER->id, (int)$announcement->id);

    $url = new \moodle_url('/blocks/dashboardannouncements/view.php', ['id' => (int)$announcement->id]);
    $message = trim(strip_tags((string)$announcement->message));
    $messagepreview = shorten_text($message, 220, true, '');
    $popupid = 'dashboardannouncements-popup-' . (int)$announcement->id;

    $closebutton = \html_writer::tag('button', get_string('popupclose', 'block_dashboardannouncements'), [
        'type' => 'button',
        'class' => 'dashboardannouncements-popup__close',
        'data-popup-dismiss' => $popupid,
    ]);

    $content = \html_writer::div(
        \html_writer::tag('h3', format_string($announcement->title), ['class' => 'dashboardannouncements-popup__title']) .
        \html_writer::tag('p', s($messagepreview), ['class' => 'dashboardannouncements-popup__body']) .
        \html_writer::link($url, get_string('popupreadannouncement', 'block_dashboardannouncements'), [
            'class' => 'btn btn-primary btn-sm dashboardannouncements-popup__link',
        ]),
        'dashboardannouncements-popup__content'
    );

    $popup = \html_writer::div(
        $closebutton . $content,
        'dashboardannouncements-popup',
        [
            'id' => $popupid,
            'role' => 'dialog',
            'aria-live' => 'polite',
            'aria-label' => get_string('popuptitle', 'block_dashboardannouncements'),
        ]
    );

    $script = \html_writer::script(
        "document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-popup-dismiss]');
            if (!btn) {
                return;
            }
            var target = document.getElementById(btn.getAttribute('data-popup-dismiss'));
            if (target) {
                target.style.display = 'none';
            }
        });"
    );

    $style = \html_writer::tag('style', "
        .dashboardannouncements-popup-wrap {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            width: min(28rem, calc(100vw - 2rem));
            z-index: 1040;
        }
        .dashboardannouncements-popup {
            position: relative;
            border: 1px solid #b7c2d1;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 24px rgba(17, 24, 39, 0.08);
            padding: 1rem;
        }
        .dashboardannouncements-popup__close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            border: 0;
            background: transparent;
            color: #5b687c;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .dashboardannouncements-popup__title {
            margin: 0 2.5rem 0.5rem 0;
            color: #142033;
            font-size: 1.125rem;
            line-height: 1.25;
        }
        .dashboardannouncements-popup__body {
            margin: 0 0 0.75rem;
            color: #5b687c;
            line-height: 1.5;
        }
        @media (max-width: 767px) {
            .dashboardannouncements-popup-wrap {
                left: 0.75rem;
                right: 0.75rem;
                width: auto;
            }
        }
    ");

    return $style . \html_writer::div($popup, 'dashboardannouncements-popup-wrap') . $script;
}
