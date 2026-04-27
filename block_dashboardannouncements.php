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
        $announcements = $manager->get_announcements_for_user((int)$USER->id, 10);

        if (!$announcements) {
            $this->content->text = html_writer::div(
                get_string('noannouncements', 'block_dashboardannouncements')
            );
        } else {
            $items = [];
            foreach ($announcements as $announcement) {
                $url = new moodle_url('/blocks/dashboardannouncements/view.php', ['id' => $announcement->id]);
                $items[] = html_writer::link($url, format_string($announcement->title));
            }

            $this->content->text = html_writer::alist($items);
        }

        if (has_capability('block/dashboardannouncements:viewall', $systemcontext)) {
            $viewallurl = new moodle_url('/blocks/dashboardannouncements/view.php');
            $this->content->footer = html_writer::link(
                $viewallurl,
                get_string('viewall', 'block_dashboardannouncements')
            );
        }

        if (has_capability('block/dashboardannouncements:manage', $systemcontext)) {
            $addurl = new moodle_url('/blocks/dashboardannouncements/edit.php');
            $manageurl = new moodle_url('/blocks/dashboardannouncements/manage.php');
            $addlink = html_writer::link($addurl, get_string('addannouncement', 'block_dashboardannouncements'));
            $managelink = html_writer::link($manageurl, get_string('manageannouncements', 'block_dashboardannouncements'));
            $links = $addlink . html_writer::empty_tag('br') . $managelink;
            $this->content->footer .= $this->content->footer ? html_writer::empty_tag('br') . $links : $links;
        }

        return $this->content;
    }
}
