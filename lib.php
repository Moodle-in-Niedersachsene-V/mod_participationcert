<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library callbacks for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_participationcert\local\issue_manager;

/**
 * Declares the features this activity supports.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True, false, null or a purpose string.
 */
function participationcert_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ADMINISTRATION;
        default:
            return null;
    }
}

/**
 * Copies the editor field of the settings form into the instance record.
 *
 * @param stdClass $data Form data.
 * @return stdClass Record ready to be stored.
 */
function participationcert_prepare_record(stdClass $data): stdClass {
    if (isset($data->topicseditor)) {
        $data->topics = $data->topicseditor['text'];
        $data->topicsformat = $data->topicseditor['format'];
        unset($data->topicseditor);
    }
    $data->certtitle = trim($data->certtitle ?? '');
    $data->scope = trim($data->scope ?? '');
    $data->emailcertificate = empty($data->emailcertificate) ? 0 : 1;
    $data->timemodified = time();
    return $data;
}

/**
 * Creates a new activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_participationcert_mod_form|null $mform The form.
 * @return int New instance id.
 */
function participationcert_add_instance($data, $mform = null) {
    global $DB;

    $record = participationcert_prepare_record($data);
    $record->timecreated = $record->timemodified;
    return $DB->insert_record('participationcert', $record);
}

/**
 * Updates an activity instance.
 *
 * @param stdClass $data Form data.
 * @param mod_participationcert_mod_form|null $mform The form.
 * @return bool True on success.
 */
function participationcert_update_instance($data, $mform = null) {
    global $DB;

    $record = participationcert_prepare_record($data);
    $record->id = $record->instance;
    return $DB->update_record('participationcert', $record);
}

/**
 * Deletes an activity instance with all issued certificates.
 *
 * @param int $id Instance id.
 * @return bool True when the instance existed and was deleted.
 */
function participationcert_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('participationcert', ['id' => $id])) {
        return false;
    }
    issue_manager::delete_all((int) $id);
    $DB->delete_records('participationcert', ['id' => $id]);
    return true;
}

/**
 * Adds the reset option for this activity to the course reset form.
 *
 * @param MoodleQuickForm $mform The course reset form.
 * @return void
 */
function participationcert_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'participationcertheader', get_string('modulenameplural', 'participationcert'));
    $mform->addElement('advcheckbox', 'reset_participationcert_issues', get_string('resetissues', 'participationcert'));
}

/**
 * Default values for the course reset form.
 *
 * @param stdClass $course The course.
 * @return array Defaults.
 */
function participationcert_reset_course_form_defaults($course) {
    return ['reset_participationcert_issues' => 1];
}

/**
 * Deletes all issued certificates of the course when requested by the course reset.
 *
 * @param stdClass $data Reset form data.
 * @return array Status entries.
 */
function participationcert_reset_userdata($data) {
    global $DB;

    $status = [];
    if (empty($data->reset_participationcert_issues)) {
        return $status;
    }
    $instances = $DB->get_fieldset_select('participationcert', 'id', 'course = ?', [$data->courseid]);
    foreach ($instances as $instanceid) {
        issue_manager::delete_all((int) $instanceid);
    }
    $status[] = [
        'component' => get_string('modulenameplural', 'participationcert'),
        'item' => get_string('resetissues', 'participationcert'),
        'error' => false,
    ];
    return $status;
}

/**
 * Adds the link to the list of issued certificates to the activity navigation.
 *
 * @param settings_navigation $settings Settings navigation.
 * @param navigation_node|null $node Activity settings node.
 * @return void
 */
function participationcert_extend_settings_navigation(settings_navigation $settings, ?navigation_node $node = null) {
    $cm = $settings->get_page()->cm;
    if (!$cm || !$node || !has_capability('mod/participationcert:viewreport', $cm->context)) {
        return;
    }
    $node->add(
        get_string('issuedcertificates', 'participationcert'),
        new moodle_url('/mod/participationcert/report.php', ['id' => $cm->id]),
        navigation_node::TYPE_SETTING,
        null,
        'participationcert_report'
    );
}
