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
 * Tests for issuing and deleting certificates and for the availability check.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(issue_manager::class)]
final class issue_manager_test extends \advanced_testcase {
    /**
     * Creates a course with a certificate activity, a student and a teacher.
     *
     * @param array $availability Access restriction tree as array, empty for none.
     * @return array [instance, cm_info, context, student, teacher]
     */
    private function create_setup(array $availability = []): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $options = $availability ? ['availability' => json_encode($availability)] : [];
        $instance = $generator->create_module('participationcert', ['course' => $course->id], $options);
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        return [$instance, $cm, \context_module::instance($cm->id), $student, $teacher];
    }

    public function test_issue_is_created_once_with_code_and_event(): void {
        $this->resetAfterTest();
        [$instance, , $context, $student] = $this->create_setup();
        $sink = $this->redirectEvents();

        $first = issue_manager::issue($instance, $context, (int) $student->id);
        $second = issue_manager::issue($instance, $context, (int) $student->id);

        $this->assertSame((int) $first->id, (int) $second->id);
        $this->assertSame($first->code, $second->code);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{' . issue_manager::CODE_LENGTH . '}$/', $first->code);
        $events = array_values(array_filter(
            $sink->get_events(),
            fn($event) => $event instanceof \mod_participationcert\event\certificate_issued
        ));
        $this->assertCount(1, $events);
        $this->assertSame((int) $student->id, (int) $events[0]->relateduserid);
    }

    public function test_can_receive_requires_capability_and_met_restrictions(): void {
        $this->resetAfterTest();
        set_config('enableavailability', 1);
        [, $cm, , $student, $teacher] = $this->create_setup();
        $this->assertTrue(issue_manager::can_receive($cm, (int) $student->id));
        $this->assertFalse(issue_manager::can_receive($cm, (int) $teacher->id));

        $future = [
            'op' => '&',
            'c' => [['type' => 'date', 'd' => '>=', 't' => time() + DAYSECS]],
            'showc' => [true],
        ];
        [, $restricted, , $waiting] = $this->create_setup($future);
        $this->assertFalse(issue_manager::can_receive($restricted, (int) $waiting->id));
    }

    public function test_get_all_lists_recipients_and_delete_removes_one(): void {
        $this->resetAfterTest();
        [$instance, $cm, $context, $student] = $this->create_setup();
        $other = $this->getDataGenerator()->create_and_enrol($cm->get_course(), 'student');
        $issue = issue_manager::issue($instance, $context, (int) $student->id);
        issue_manager::issue($instance, $context, (int) $other->id);
        $this->assertCount(2, issue_manager::get_all((int) $instance->id));

        $sink = $this->redirectEvents();
        issue_manager::delete($issue, $context);

        $this->assertNull(issue_manager::get((int) $instance->id, (int) $student->id));
        $this->assertCount(1, issue_manager::get_all((int) $instance->id));
        $this->assertInstanceOf(\mod_participationcert\event\issue_deleted::class, $sink->get_events()[0]);
    }

    public function test_mark_emailed_stores_the_time(): void {
        $this->resetAfterTest();
        [$instance, , $context, $student] = $this->create_setup();
        $issue = issue_manager::issue($instance, $context, (int) $student->id);

        issue_manager::mark_emailed($issue);

        $this->assertGreaterThan(0, (int) issue_manager::get((int) $instance->id, (int) $student->id)->timeemailed);
    }
}
