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

use block_dashboardannouncements\local\presentation_helper;

/**
 * Dashboard announcements block.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_dashboardannouncements extends block_base {
    public function init(): void {
        $this->title = get_string('pluginname', 'block_dashboardannouncements');
    }

    public function applicable_formats(): array {
        return [
            'my' => true,
            'site-index' => true,
        ];
    }

    public function has_config(): bool {
        return false;
    }

    public function get_content() {
        global $OUTPUT, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $systemcontext = \context_system::instance();
        if (!isloggedin() || isguestuser() || !has_capability('block/dashboardannouncements:view', $systemcontext)) {
            return $this->content;
        }

        $manager = new \block_dashboardannouncements\local\announcement_manager();
        $announcements = $manager->get_announcements_for_user((int)$USER->id, 5);

        if (!$announcements) {
            $emptystate = presentation_helper::get_empty_state_copy('default');
            $this->content->text = presentation_helper::render_empty_state(
                $emptystate['title'],
                $emptystate['description']
            );
        } else {
            $cards = [];
            foreach ($announcements as $announcement) {
                $url = new moodle_url('/blocks/dashboardannouncements/view.php', ['id' => $announcement->id]);
                $title = html_writer::link($url, format_string($announcement->title));
                $attachment = $manager->get_attachment_file((int)$announcement->id);
                $hasattachment = (bool)$attachment;
                $messagepreview = presentation_helper::get_compact_message_preview((string)$announcement->message);
                $submitteddate = presentation_helper::format_metadata_datetime((int)($announcement->timecreated ?? 0));

                $cardcontent = html_writer::div(
                    html_writer::tag(
                        'h4',
                        $title,
                        ['class' => 'dashboardannouncement-card__title']
                    ) . presentation_helper::render_attachment_indicator($hasattachment),
                    'dashboardannouncement-card__header'
                );
                $cardcontent .= html_writer::div(
                    s(get_string('submittedon', 'block_dashboardannouncements', $submitteddate)),
                    'dashboardannouncement-card__meta dashboardannouncements-subtle'
                );

                if ($messagepreview !== '') {
                    $cardcontent .= html_writer::div(s($messagepreview), 'dashboardannouncement-card__body');
                }

                $cardclasses = 'dashboardannouncement-card dashboardannouncement-card--compact';
                if (!empty($announcement->showaspopup)) {
                    $cardclasses .= ' dashboardannouncement-card--popup';
                }
                $cards[] = html_writer::div($cardcontent, $cardclasses);
            }

            $this->content->text = html_writer::div(implode('', $cards), 'dashboardannouncement-list');
        }

        if (has_capability('block/dashboardannouncements:viewall', $systemcontext)) {
            $viewallurl = new moodle_url('/blocks/dashboardannouncements/view.php');
            $this->content->footer = presentation_helper::render_action_links([
                [
                    'url' => $viewallurl,
                    'label' => get_string('viewall', 'block_dashboardannouncements'),
                ],
            ]);
        }

        if (has_capability('block/dashboardannouncements:manage', $systemcontext)) {
            $addurl = new moodle_url('/blocks/dashboardannouncements/edit.php');
            $manageurl = new moodle_url('/blocks/dashboardannouncements/manage.php');
            $manageractions = presentation_helper::render_action_links([
                [
                    'url' => $addurl,
                    'label' => get_string('addannouncement', 'block_dashboardannouncements'),
                ],
                [
                    'url' => $manageurl,
                    'label' => get_string('manageannouncements', 'block_dashboardannouncements'),
                ],
            ]);
            $this->content->footer = $this->content->footer . $manageractions;
        }

        return $this->content;
    }
}
