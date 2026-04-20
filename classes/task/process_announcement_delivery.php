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

namespace block_dashboardannouncements\task;

defined('MOODLE_INTERNAL') || die();

use block_dashboardannouncements\local\announcement_manager;
use block_dashboardannouncements\local\delivery_manager;
use core\task\scheduled_task;

/**
 * Process queued announcement delivery.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_announcement_delivery extends scheduled_task {
    public function get_name(): string {
        return get_string('taskprocessdelivery', 'block_dashboardannouncements');
    }

    public function execute(): void {
        global $CFG;

        $announcementmanager = new announcement_manager();
        $deliverymanager = new delivery_manager();
        $queues = $deliverymanager->get_pending_queue_items();

        foreach ($queues as $queue) {
            $deliverymanager->mark_queue_processing($queue);
            $announcement = $announcementmanager->get_announcement((int)$queue->announcementid, IGNORE_MISSING);

            if (!$announcement || $announcement->status !== 'published' || !empty($announcement->archived)) {
                $deliverymanager->finish_queue($queue, 'failed', 'Announcement is no longer deliverable.');
                continue;
            }

            $resolver = new \block_dashboardannouncements\local\audience_resolver();
            $recipients = $resolver->get_target_users($announcement);
            $hasfailures = false;
            $hassuccesses = false;

            foreach ($recipients as $recipient) {
                if ($deliverymanager->was_successfully_sent((int)$announcement->id, (int)$recipient->id, 'message')) {
                    continue;
                }

                try {
                    $result = $this->send_message($announcement, $recipient, $CFG->wwwroot);
                    if ($result) {
                        $deliverymanager->log_delivery_result(
                            (int)$queue->id,
                            (int)$announcement->id,
                            (int)$recipient->id,
                            'message',
                            'sent'
                        );
                        $hassuccesses = true;
                    } else {
                        $deliverymanager->log_delivery_result(
                            (int)$queue->id,
                            (int)$announcement->id,
                            (int)$recipient->id,
                            'message',
                            'failed',
                            null,
                            'Message API returned false.'
                        );
                        $hasfailures = true;
                    }
                } catch (\Throwable $e) {
                    $deliverymanager->log_delivery_result(
                        (int)$queue->id,
                        (int)$announcement->id,
                        (int)$recipient->id,
                        'message',
                        'failed',
                        null,
                        $e->getMessage()
                    );
                    $hasfailures = true;
                }
            }

            $status = $hasfailures ? ($hassuccesses ? 'partial' : 'failed') : 'completed';
            $deliverymanager->finish_queue($queue, $status, null);
        }
    }

    /**
     * Send through Moodle messaging.
     *
     * @param \stdClass $announcement
     * @param \stdClass $recipient
     * @param string $wwwroot
     * @return bool
     */
    protected function send_message(\stdClass $announcement, \stdClass $recipient, string $wwwroot): bool {
        global $CFG;

        require_once($CFG->dirroot . '/message/lib.php');

        $message = new \core\message\message();
        $message->component = 'block_dashboardannouncements';
        $message->name = 'announcement';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $recipient;
        $message->subject = $announcement->title;
        $message->fullmessage = html_to_text(format_text($announcement->message, FORMAT_HTML));
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = format_text($announcement->message, FORMAT_HTML);
        $message->smallmessage = shorten_text(strip_tags($announcement->message), 80);
        $message->notification = 1;
        $message->contexturl = $wwwroot . '/blocks/dashboardannouncements/view.php?id=' . $announcement->id;
        $message->contexturlname = $announcement->title;
        return (bool)message_send($message);
    }
}
