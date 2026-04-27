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

namespace block_dashboardannouncements\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use block_dashboardannouncements\local\audience_resolver;
use moodleform;

/**
 * Announcement create/edit form.
 *
 * @package   block_dashboardannouncements
 * @copyright 2026 OpenAI
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class announcement_form extends moodleform {
    public function definition(): void {
        global $DB;

        $mform = $this->_form;
        $resolver = new audience_resolver();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'contentsection', get_string('formsection:content', 'block_dashboardannouncements'));
        $mform->setExpanded('contentsection', true);
        $mform->addElement('text', 'title', get_string('title', 'block_dashboardannouncements'), ['size' => 80]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('editor', 'message', get_string('message', 'block_dashboardannouncements'));
        $mform->setType('message', PARAM_RAW);

        $mform->addElement('header', 'schedulesection', get_string('formsection:schedule', 'block_dashboardannouncements'));
        $mform->setExpanded('schedulesection', true);
        $statusoptions = [
            'draft' => get_string('status:draft', 'block_dashboardannouncements'),
            'published' => get_string('status:published', 'block_dashboardannouncements'),
            'disabled' => get_string('status:disabled', 'block_dashboardannouncements'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'block_dashboardannouncements'), $statusoptions);

        $mform->addElement('date_time_selector', 'timestart', get_string('startdate', 'block_dashboardannouncements'), ['optional' => false]);
        $mform->addElement('date_time_selector', 'timeend', get_string('enddate', 'block_dashboardannouncements'), ['optional' => true]);
        $mform->addElement('advcheckbox', 'showaspopup', get_string('showaspopup', 'block_dashboardannouncements'));

        $mform->addElement('header', 'audiencesection', get_string('formsection:audience', 'block_dashboardannouncements'));
        $mform->setExpanded('audiencesection', true);
        $targetoptions = [
            audience_resolver::TARGET_ALL => get_string('targettype:all', 'block_dashboardannouncements'),
            audience_resolver::TARGET_CATEGORY => get_string('targettype:category', 'block_dashboardannouncements'),
            audience_resolver::TARGET_COHORT => get_string('targettype:cohort', 'block_dashboardannouncements'),
            audience_resolver::TARGET_FIELD => get_string('targettype:field', 'block_dashboardannouncements'),
        ];
        $mform->addElement('select', 'targettype', get_string('targettype', 'block_dashboardannouncements'), $targetoptions);

        $categoryoptions = [];
        $categories = $DB->get_records('course_categories', null, 'name ASC', 'id, name');
        foreach ($categories as $category) {
            $categoryoptions[$category->id] = format_string($category->name);
        }
        $mform->addElement('autocomplete', 'categoryids', get_string('categoryids', 'block_dashboardannouncements'), $categoryoptions, ['multiple' => true]);
        $mform->hideIf('categoryids', 'targettype', 'neq', audience_resolver::TARGET_CATEGORY);

        $cohortoptions = [];
        $cohorts = $DB->get_records('cohort', null, 'name ASC', 'id, name');
        foreach ($cohorts as $cohort) {
            $cohortoptions[$cohort->id] = format_string($cohort->name);
        }
        $mform->addElement('autocomplete', 'cohortids', get_string('cohortids', 'block_dashboardannouncements'), $cohortoptions, ['multiple' => true]);
        $mform->hideIf('cohortids', 'targettype', 'neq', audience_resolver::TARGET_COHORT);

        $mform->addElement(
            'autocomplete',
            'fieldlookup',
            get_string('fieldlookup', 'block_dashboardannouncements'),
            $resolver->get_field_options(),
            ['multiple' => false]
        );
        $mform->hideIf('fieldlookup', 'targettype', 'neq', audience_resolver::TARGET_FIELD);

        $fieldoperators = [
            audience_resolver::OP_CONTAINS => get_string('fieldoperator:contains', 'block_dashboardannouncements'),
            audience_resolver::OP_NOTCONTAINS => get_string('fieldoperator:notcontains', 'block_dashboardannouncements'),
            audience_resolver::OP_EQUAL => get_string('fieldoperator:equal', 'block_dashboardannouncements'),
            audience_resolver::OP_STARTSWITH => get_string('fieldoperator:startswith', 'block_dashboardannouncements'),
            audience_resolver::OP_ENDSWITH => get_string('fieldoperator:endswith', 'block_dashboardannouncements'),
            audience_resolver::OP_ISEMPTY => get_string('fieldoperator:isempty', 'block_dashboardannouncements'),
            audience_resolver::OP_ISNOTEMPTY => get_string('fieldoperator:isnotempty', 'block_dashboardannouncements'),
        ];
        $mform->addElement('select', 'fieldoperator', get_string('fieldoperator', 'block_dashboardannouncements'), $fieldoperators);
        $mform->hideIf('fieldoperator', 'targettype', 'neq', audience_resolver::TARGET_FIELD);

        $mform->addElement('text', 'fieldmatchvalue', get_string('fieldmatchvalue', 'block_dashboardannouncements'));
        $mform->setType('fieldmatchvalue', PARAM_TEXT);
        $mform->hideIf('fieldmatchvalue', 'targettype', 'neq', audience_resolver::TARGET_FIELD);
        $mform->hideIf('fieldmatchvalue', 'fieldoperator', 'eq', audience_resolver::OP_ISEMPTY);
        $mform->hideIf('fieldmatchvalue', 'fieldoperator', 'eq', audience_resolver::OP_ISNOTEMPTY);

        $mform->addElement('header', 'deliverysection', get_string('formsection:delivery', 'block_dashboardannouncements'));
        $mform->setExpanded('deliverysection', true);
        $sendoptions = [
            'none' => get_string('sendmode:none', 'block_dashboardannouncements'),
            'message' => get_string('sendmode:message', 'block_dashboardannouncements'),
        ];
        $mform->addElement('select', 'sendmode', get_string('sendmode', 'block_dashboardannouncements'), $sendoptions);

        $mform->addElement('header', 'attachmentsection', get_string('formsection:attachment', 'block_dashboardannouncements'));
        $mform->setExpanded('attachmentsection', true);
        $attachmentoptions = [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => '*',
        ];
        $mform->addElement('filemanager', 'attachment_filemanager', get_string('attachment', 'block_dashboardannouncements'), null, $attachmentoptions);

        $mform->addElement('header', 'recordsection', get_string('formsection:recordstate', 'block_dashboardannouncements'));
        $mform->setExpanded('recordsection', true);
        $mform->addElement('advcheckbox', 'archived', get_string('status:archived', 'block_dashboardannouncements'));

        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $resolver = new audience_resolver();

        if (!empty($data['timeend']) && !empty($data['timestart']) && $data['timeend'] < $data['timestart']) {
            $errors['timeend'] = get_string('invaliddatewindow', 'block_dashboardannouncements');
        }

        switch ($data['targettype']) {
            case audience_resolver::TARGET_CATEGORY:
                if (empty($data['categoryids'])) {
                    $errors['categoryids'] = get_string('invalidtargetconfig', 'block_dashboardannouncements');
                }
                break;

            case audience_resolver::TARGET_COHORT:
                if (empty($data['cohortids'])) {
                    $errors['cohortids'] = get_string('invalidtargetconfig', 'block_dashboardannouncements');
                }
                break;

            case audience_resolver::TARGET_FIELD:
                $fieldlookup = $data['fieldlookup'] ?? '';
                if (is_array($fieldlookup)) {
                    $fieldlookup = (string)reset($fieldlookup);
                }

                if (trim((string)$fieldlookup) === '') {
                    $errors['fieldlookup'] = get_string('missingfieldselection', 'block_dashboardannouncements');
                } else if (
                    $resolver->operator_uses_value((string)$data['fieldoperator']) &&
                    trim((string)($data['fieldmatchvalue'] ?? '')) === ''
                ) {
                    $errors['fieldmatchvalue'] = get_string('invalidfieldmatchvalue', 'block_dashboardannouncements');
                }
                break;
        }

        return $errors;
    }
}
