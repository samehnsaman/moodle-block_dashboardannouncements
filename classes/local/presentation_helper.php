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

namespace block_dashboardannouncements\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared rendering helpers for announcement presentation.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presentation_helper {
    /** @var int */
    public const COMPACT_MESSAGE_LENGTH = 50;
    /**
     * Canonical badge modifier per known status.
     *
     * @var string[]
     */
    protected const STATUS_BADGE_MODIFIER = [
        'draft' => 'draft',
        'published' => 'published',
        'disabled' => 'disabled',
        'archived' => 'archived',
    ];

    /**
     * Return a user-facing status label.
     *
     * @param string $status
     * @return string
     */
    public static function get_status_label(string $status): string {
        $status = trim(\core_text::strtolower($status));
        $identifier = 'status:' . $status;

        if (get_string_manager()->string_exists($identifier, 'block_dashboardannouncements')) {
            return get_string($identifier, 'block_dashboardannouncements');
        }

        $unknownlabel = get_string('status:unknown', 'block_dashboardannouncements');
        if ($status === '') {
            return $unknownlabel;
        }

        return $unknownlabel . ' (' . ucfirst($status) . ')';
    }

    /**
     * Render a status badge with shared visual classes.
     *
     * @param string $status
     * @param string|null $label
     * @return string
     */
    public static function render_status_badge(string $status, ?string $label = null): string {
        $status = trim(\core_text::strtolower($status));
        $label = $label ?? self::get_status_label($status);
        $modifier = self::STATUS_BADGE_MODIFIER[$status] ?? 'alert';
        $classes = 'dashboardannouncements-status-badge is-' . $modifier;

        return \html_writer::span(s($label), $classes);
    }

    /**
     * Format a timestamp for metadata displays.
     *
     * @param int|null $timestamp
     * @param string $fallbackkey
     * @return string
     */
    public static function format_metadata_datetime(?int $timestamp, string $fallbackkey = 'metadatanotavailable'): string {
        if (!empty($timestamp)) {
            return userdate($timestamp, get_string('strftimedatetime', 'langconfig'));
        }

        return get_string($fallbackkey, 'block_dashboardannouncements');
    }

    /**
     * Format a metadata value with a plugin fallback string.
     *
     * @param string|null $value
     * @param string $fallbackkey
     * @return string
     */
    public static function format_metadata_value(?string $value, string $fallbackkey = 'metadatanotavailable'): string {
        $value = trim((string)$value);
        if ($value !== '') {
            return $value;
        }

        return get_string($fallbackkey, 'block_dashboardannouncements');
    }

    /**
     * Build canonical end-user metadata items.
     *
     * @param int|null $timestart
     * @param string $attachmentname
     * @param string|null $postername
     * @return array
     */
    public static function build_end_user_metadata_items(
        ?int $timestart,
        string $attachmentname,
        ?string $postername = null
    ): array {
        $items = [
            [
                'label' => get_string('date', 'block_dashboardannouncements'),
                'value' => self::format_metadata_datetime($timestart),
            ],
            [
                'label' => get_string('attachment', 'block_dashboardannouncements'),
                'value' => self::format_metadata_value($attachmentname, 'noattachment'),
            ],
        ];

        if ($postername !== null) {
            $items[] = [
                'label' => get_string('postedby', 'block_dashboardannouncements'),
                'value' => self::format_metadata_value($postername),
            ];
        }

        return $items;
    }

    /**
     * Build canonical attachment display payload.
     *
     * @param int $contextid
     * @param int $announcementid
     * @param mixed $attachment
     * @return array{name:string,actionhtml:string}
     */
    public static function build_attachment_display(int $contextid, int $announcementid, $attachment): array {
        if (!$attachment) {
            return [
                'name' => get_string('noattachment', 'block_dashboardannouncements'),
                'actionhtml' => '',
            ];
        }

        $attachmenturl = \moodle_url::make_pluginfile_url(
            $contextid,
            'block_dashboardannouncements',
            announcement_manager::ATTACHMENT_FILEAREA,
            $announcementid,
            '/',
            $attachment->get_filename()
        );

        return [
            'name' => $attachment->get_filename(),
            'actionhtml' => \html_writer::link(
                $attachmenturl,
                get_string('attachmentdownload', 'block_dashboardannouncements')
            ),
        ];
    }

    /**
     * Build compact plain-text message preview.
     *
     * @param string $messagehtml
     * @param int $length
     * @return string
     */
    public static function get_compact_message_preview(string $messagehtml, int $length = self::COMPACT_MESSAGE_LENGTH): string {
        $messagetext = trim(strip_tags(format_text($messagehtml, FORMAT_HTML)));
        return shorten_text($messagetext, $length, true, '');
    }

    /**
     * Build a compact mobile payload for one announcement.
     *
     * @param \stdClass $announcement
     * @param bool $hasattachment
     * @param bool $includemanagerdetails
     * @return array
     */
    public static function build_mobile_preview_payload(
        \stdClass $announcement,
        bool $hasattachment,
        bool $includemanagerdetails = false
    ): array {
        $payload = [
            'title' => trim(strip_tags(format_string((string)($announcement->title ?? '')))),
            'snippet' => self::get_compact_message_preview((string)($announcement->message ?? '')),
            'hasattachment' => $hasattachment,
            'attachmentlabel' => get_string('mobileattachmentlabel', 'block_dashboardannouncements'),
            'submittedlabel' => get_string(
                'submittedon',
                'block_dashboardannouncements',
                self::format_metadata_datetime((int)($announcement->timecreated ?? 0))
            ),
            'openlabel' => get_string('mobileopenannouncement', 'block_dashboardannouncements'),
            'hasmanagermeta' => false,
            'managermetalabel' => '',
        ];

        if ($includemanagerdetails) {
            $status = self::get_status_label((string)($announcement->status ?? ''));
            $date = self::format_metadata_datetime((int)($announcement->timecreated ?? 0));
            $payload['hasmanagermeta'] = true;
            $payload['managermetalabel'] = get_string('mobilemanagermeta', 'block_dashboardannouncements', (object)[
                'status' => $status,
                'date' => $date,
            ]);
        }

        return $payload;
    }

    /**
     * Render compact attachment indicator icon.
     *
     * @param bool $hasattachment
     * @return string
     */
    public static function render_attachment_indicator(bool $hasattachment): string {
        global $OUTPUT;

        if (!$hasattachment) {
            return '';
        }

        $icon = new \pix_icon('i/files', get_string('attachment', 'block_dashboardannouncements'));
        return \html_writer::span(
            $OUTPUT->render($icon),
            'dashboardannouncements-attachment-indicator',
            [
                'title' => get_string('attachment', 'block_dashboardannouncements'),
                'aria-label' => get_string('attachment', 'block_dashboardannouncements'),
            ]
        );
    }

    /**
     * Build canonical manager metadata items.
     *
     * @param string $audiencesummary
     * @param string $targetedcount
     * @param string $notifiedcount
     * @return array
     */
    public static function build_manager_metadata_items(
        string $audiencesummary,
        string $targetedcount,
        string $notifiedcount
    ): array {
        return [
            [
                'label' => get_string('audiencesummary', 'block_dashboardannouncements'),
                'value' => self::format_metadata_value($audiencesummary),
            ],
            [
                'label' => get_string('targetedcount', 'block_dashboardannouncements'),
                'value' => self::format_metadata_value($targetedcount, 'targetedblank'),
            ],
            [
                'label' => get_string('notifiedcount', 'block_dashboardannouncements'),
                'value' => self::format_metadata_value($notifiedcount, 'metadatanotavailable'),
            ],
        ];
    }

    /**
     * Render a consistent action cluster.
     *
     * @param array $actions
     * @return string
     */
    public static function render_action_links(array $actions): string {
        if (!$actions) {
            return '';
        }

        $rendered = [];
        foreach ($actions as $action) {
            if (!empty($action['url']) && !empty($action['label'])) {
                $rendered[] = \html_writer::link($action['url'], $action['label']);
            }
        }

        if (!$rendered) {
            return '';
        }

        return \html_writer::div(implode('', $rendered), 'dashboardannouncements-actions');
    }

    /**
     * Render metadata items as a styled list.
     *
     * @param array $items
     * @param string $listclass
     * @return string
     */
    public static function render_metadata_list(array $items, string $listclass = 'dashboardannouncements-meta-list'): string {
        if (!$items) {
            return '';
        }

        $rendereditems = [];
        foreach ($items as $item) {
            $label = trim((string)($item['label'] ?? ''));
            $value = self::format_metadata_value($item['value'] ?? '');
            $content = \html_writer::span(s($label) . ': ', 'dashboardannouncements-meta-item__label') .
                \html_writer::span(s($value), 'dashboardannouncements-meta-item__value');
            $rendereditems[] = \html_writer::tag('li', $content, ['class' => 'dashboardannouncements-meta-item']);
        }

        return \html_writer::tag('ul', implode('', $rendereditems), ['class' => $listclass]);
    }

    /**
     * Render a reusable empty-state panel.
     *
     * @param string $title
     * @param string $message
     * @param string $actionshtml
     * @return string
     */
    public static function render_empty_state(string $title = '', string $message = '', string $actionshtml = ''): string {
        if (trim($title) === '') {
            $title = get_string('emptystate:title', 'block_dashboardannouncements');
        }
        if (trim($message) === '') {
            $message = get_string('emptystate:description', 'block_dashboardannouncements');
        }

        $output = \html_writer::tag('h3', s($title), ['class' => 'dashboardannouncements-empty__title']);
        $output .= \html_writer::tag('p', s($message), ['class' => 'dashboardannouncements-empty__text']);

        if ($actionshtml !== '') {
            $output .= \html_writer::div($actionshtml, 'dashboardannouncements-actions');
        }

        return \html_writer::div($output, 'dashboardannouncements-empty');
    }

    /**
     * Return canonical empty-state copy for a UI surface.
     *
     * @param string $contextkey
     * @return array{title:string,description:string}
     */
    public static function get_empty_state_copy(string $contextkey = 'default'): array {
        $contextkey = trim(\core_text::strtolower($contextkey));
        $contexts = [
            'default' => [
                'title' => get_string('emptystate:title', 'block_dashboardannouncements'),
                'description' => get_string('emptystate:description', 'block_dashboardannouncements'),
            ],
            'list' => [
                'title' => get_string('emptystate:list:title', 'block_dashboardannouncements'),
                'description' => get_string('emptystate:list:description', 'block_dashboardannouncements'),
            ],
            'manage' => [
                'title' => get_string('emptystate:manage:title', 'block_dashboardannouncements'),
                'description' => get_string('emptystate:manage:description', 'block_dashboardannouncements'),
            ],
        ];

        return $contexts[$contextkey] ?? $contexts['default'];
    }
}
