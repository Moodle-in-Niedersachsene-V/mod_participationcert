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

namespace mod_participationcert\local;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for sending certificates by email.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(delivery::class)]
final class delivery_test extends \advanced_testcase {
    public function test_run_for_all_sends_once_to_each_participant_with_pdf_attachment(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Erste Hilfe']);
        $first = $generator->create_and_enrol($course, 'student');
        $second = $generator->create_and_enrol($course, 'student');
        $generator->create_and_enrol($course, 'editingteacher');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        $record = $DB->get_record('participationcert', ['id' => $instance->id]);
        $sink = $this->redirectEmails();

        $sent = delivery::run($cm, $record, 0, false);

        $this->assertSame(2, $sent);
        $messages = $sink->get_messages();
        $this->assertCount(2, $messages);
        $recipients = array_map(fn($message) => $message->to, $messages);
        sort($recipients);
        $expected = [$first->email, $second->email];
        sort($expected);
        $this->assertSame($expected, $recipients);
        $this->assertStringContainsString('Erste Hilfe', $messages[0]->subject);
        $this->assertStringContainsString('application/pdf', $messages[0]->body);
        $this->assertGreaterThan(0, (int) issue_manager::get((int) $instance->id, (int) $first->id)->timeemailed);

        $sink->clear();
        $this->assertSame(0, delivery::run($cm, $record, 0, false));
        $this->assertSame(1, delivery::run($cm, $record, (int) $first->id, true));
        $this->assertCount(1, $sink->get_messages());
    }

    public function test_run_skips_users_who_do_not_meet_the_restrictions(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('enableavailability', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $future = ['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => time() + DAYSECS]], 'showc' => [true]];
        $instance = $generator->create_module(
            'participationcert',
            ['course' => $course->id],
            ['availability' => json_encode($future)]
        );
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        $sink = $this->redirectEmails();

        $sent = delivery::run($cm, $DB->get_record('participationcert', ['id' => $instance->id]), (int) $student->id, true);

        $this->assertSame(0, $sent);
        $this->assertCount(0, $sink->get_messages());
        $this->assertNull(issue_manager::get((int) $instance->id, (int) $student->id));
    }
}
