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

namespace mod_participationcert\completion;

use mod_participationcert\local\issue_manager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the completion rule "certificate received".
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_completion::class)]
#[CoversClass(issue_manager::class)]
final class custom_completion_test extends \advanced_testcase {
    public function test_issuing_completes_and_deleting_reopens_the_activity(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_and_enrol($course, 'student');
        $instance = $generator->create_module(
            'participationcert',
            ['course' => $course->id, 'completionissued' => 1],
            ['completion' => COMPLETION_TRACKING_AUTOMATIC]
        );
        $context = \context_module::instance($instance->cmid);
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        $completion = new \completion_info($course);
        $rules = new custom_completion($cm, (int) $student->id);

        $this->assertSame(COMPLETION_INCOMPLETE, $rules->get_state('completionissued'));
        $this->assertSame(COMPLETION_INCOMPLETE, (int) $completion->get_data($cm, false, (int) $student->id)->completionstate);

        $issue = issue_manager::issue($instance, $context, (int) $student->id);
        $this->assertSame(COMPLETION_COMPLETE, $rules->get_state('completionissued'));
        $this->assertSame(COMPLETION_COMPLETE, (int) $completion->get_data($cm, false, (int) $student->id)->completionstate);

        issue_manager::delete($issue, $context);
        $this->assertSame(COMPLETION_INCOMPLETE, (int) $completion->get_data($cm, false, (int) $student->id)->completionstate);
    }

    public function test_rule_is_defined_and_described(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $instance = $this->getDataGenerator()->create_module(
            'participationcert',
            ['course' => $course->id, 'completionissued' => 1],
            ['completion' => COMPLETION_TRACKING_AUTOMATIC]
        );
        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        $rules = new custom_completion($cm, (int) $student->id);

        $this->assertSame(['completionissued'], custom_completion::get_defined_custom_rules());
        $this->assertSame(['completionissued'], $rules->get_available_custom_rules());
        $this->assertSame(
            get_string('completiondetail:issued', 'participationcert'),
            $rules->get_custom_rule_descriptions()['completionissued']
        );
    }
}
