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
 * Event observers for mod_participationcert.
 *
 * Progress events may fulfil the access restrictions of a certificate. The observer only queues an
 * ad hoc task; the task checks the restrictions and sends the email.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\mod_participationcert\observer::progress_changed',
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\mod_participationcert\observer::progress_changed',
    ],
    [
        'eventname' => '\core\event\user_graded',
        'callback' => '\mod_participationcert\observer::progress_changed',
    ],
];
