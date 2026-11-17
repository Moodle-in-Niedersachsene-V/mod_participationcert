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
 * Activity page: download button for participants, link to the issued certificates for teachers.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_participationcert\local\issue_manager;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'participationcert');
$instance = $DB->get_record('participationcert', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/participationcert:view', $context);

$PAGE->set_url('/mod/participationcert/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($instance);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);
\mod_participationcert\event\course_module_viewed::create([
    'objectid' => $instance->id,
    'context' => $context,
])->trigger();

$canreceive = has_capability('mod/participationcert:receive', $context, null, false);
$canviewreport = has_capability('mod/participationcert:viewreport', $context);

echo $OUTPUT->header();

if ($canreceive && issue_manager::can_receive($cm, (int) $USER->id)) {
    $issue = issue_manager::get((int) $instance->id, (int) $USER->id);
    $message = $issue
        ? get_string('issuedon', 'participationcert', userdate($issue->timecreated, get_string('strftimedate', 'langconfig')))
        : get_string('certificateready', 'participationcert');
    echo html_writer::tag('p', s($message));
    echo $OUTPUT->single_button(
        new moodle_url('/mod/participationcert/download.php', ['id' => $cm->id]),
        get_string('downloadcertificate', 'participationcert'),
        'post',
        ['type' => single_button::BUTTON_PRIMARY]
    );
} else if ($canreceive) {
    echo $OUTPUT->notification(get_string('notavailable', 'participationcert'), \core\output\notification::NOTIFY_INFO);
} else if (!$canviewreport) {
    echo $OUTPUT->notification(get_string('nocertificateforyou', 'participationcert'), \core\output\notification::NOTIFY_INFO);
}

if ($canviewreport) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/mod/participationcert/preview.php', ['id' => $cm->id]),
            get_string('previewcertificate', 'participationcert'),
            ['class' => 'btn btn-secondary', 'target' => '_blank']
        ) . $OUTPUT->help_icon('previewcertificate', 'participationcert'),
        'mb-3'
    );
    $count = count(issue_manager::get_all((int) $instance->id));
    echo $OUTPUT->single_button(
        new moodle_url('/mod/participationcert/report.php', ['id' => $cm->id]),
        get_string('issuedcertificatescount', 'participationcert', $count),
        'get'
    );
}

echo $OUTPUT->footer();
