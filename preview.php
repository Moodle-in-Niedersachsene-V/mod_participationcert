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
 * Shows a preview of the certificate with the name of the current user and a watermark.
 *
 * The preview issues no certificate and stores nothing.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_participationcert\local\certificate_pdf;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'participationcert');
$instance = $DB->get_record('participationcert', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/participationcert:viewreport', $context);

$pdf = certificate_pdf::build_preview($instance, $course, $USER, $context);
$filename = clean_filename(get_string('pdf:preview', 'participationcert') . ' ' . certificate_pdf::filename($instance, $USER));
send_file($pdf, $filename, 0, 0, true, false, 'application/pdf');
