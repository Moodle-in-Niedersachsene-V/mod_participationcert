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

namespace mod_participationcert;

use mod_participationcert\local\issue_manager;
use mod_participationcert\task\send_certificates;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the observer and the ad hoc task that send certificates after progress events.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(observer::class)]
#[CoversClass(send_certificates::class)]
final class observer_test extends \advanced_testcase {
    public function test_completing_the_required_activity_sends_the_certificate(): void {
        $this->resetAfterTest();
        set_config('enableavailability', 1);
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $page = $generator->create_module('page', ['course' => $course->id], ['completion' => COMPLETION_TRACKING_MANUAL]);
        $condition = [
            'op' => '&',
            'c' => [['type' => 'completion', 'cm' => (int) $page->cmid, 'e' => COMPLETION_COMPLETE]],
            'showc' => [true],
        ];
        $instance = $generator->create_module(
            'participationcert',
            ['course' => $course->id, 'emailcertificate' => 1],
            ['availability' => json_encode($condition)]
        );
        $sink = $this->redirectEmails();

        $completion = new \completion_info($course);
        $completion->update_state(get_fast_modinfo($course)->get_cm($page->cmid), COMPLETION_COMPLETE, (int) $student->id);
        $this->assertCount(1, \core\task\manager::get_adhoc_tasks(send_certificates::class));
        $this->expectOutputRegex('/Sent 1 certificate/');
        $this->runAdhocTasks(send_certificates::class);

        $this->assertCount(1, $sink->get_messages());
        $this->assertSame($student->email, $sink->get_messages()[0]->to);
        $this->assertGreaterThan(0, (int) issue_manager::get((int) $instance->id, (int) $student->id)->timeemailed);
    }

    public function test_nothing_is_queued_when_email_is_off(): void {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $page = $generator->create_module('page', ['course' => $course->id], ['completion' => COMPLETION_TRACKING_MANUAL]);
        $generator->create_module('participationcert', ['course' => $course->id, 'emailcertificate' => 0]);

        $completion = new \completion_info($course);
        $completion->update_state(get_fast_modinfo($course)->get_cm($page->cmid), COMPLETION_COMPLETE, (int) $student->id);

        $this->assertCount(0, \core\task\manager::get_adhoc_tasks(send_certificates::class));
    }
}
