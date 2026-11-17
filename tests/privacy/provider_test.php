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

namespace mod_participationcert\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use mod_participationcert\local\issue_manager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the privacy provider.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends provider_testcase {
    /**
     * Creates an activity with certificates issued to two students.
     *
     * @return array [instance, context, first student, second student]
     */
    private function create_setup(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $first = $generator->create_and_enrol($course, 'student');
        $second = $generator->create_and_enrol($course, 'student');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        $context = \context_module::instance($instance->cmid);
        issue_manager::issue($instance, $context, (int) $first->id);
        issue_manager::issue($instance, $context, (int) $second->id);
        return [$instance, $context, $first, $second];
    }

    public function test_contexts_and_users_cover_recipients_only(): void {
        $this->resetAfterTest();
        [, $context, $first, $second] = $this->create_setup();
        $other = $this->getDataGenerator()->create_user();

        $this->assertEquals([$context->id], provider::get_contexts_for_userid((int) $first->id)->get_contextids());
        $this->assertEmpty(provider::get_contexts_for_userid((int) $other->id)->get_contextids());

        $userlist = new userlist($context, 'mod_participationcert');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        sort($userids);
        $expected = [(int) $first->id, (int) $second->id];
        sort($expected);
        $this->assertEquals($expected, $userids);
    }

    public function test_export_contains_code_and_date_of_issue(): void {
        $this->resetAfterTest();
        [$instance, $context, $first] = $this->create_setup();
        $issue = issue_manager::get((int) $instance->id, (int) $first->id);

        provider::export_user_data(new approved_contextlist($first, 'mod_participationcert', [$context->id]));

        $data = writer::with_context($context)->get_data([get_string('privacy:issue', 'participationcert')]);
        $this->assertSame($issue->code, $data->code);
        $this->assertSame('-', $data->timeemailed);
    }

    public function test_delete_for_user_keeps_other_users(): void {
        $this->resetAfterTest();
        [$instance, $context, $first, $second] = $this->create_setup();

        provider::delete_data_for_user(new approved_contextlist($first, 'mod_participationcert', [$context->id]));

        $this->assertNull(issue_manager::get((int) $instance->id, (int) $first->id));
        $this->assertNotNull(issue_manager::get((int) $instance->id, (int) $second->id));
    }

    public function test_delete_for_users_and_for_all_users(): void {
        $this->resetAfterTest();
        [$instance, $context, $first, $second] = $this->create_setup();

        provider::delete_data_for_users(new approved_userlist($context, 'mod_participationcert', [(int) $second->id]));
        $this->assertNull(issue_manager::get((int) $instance->id, (int) $second->id));
        $this->assertNotNull(issue_manager::get((int) $instance->id, (int) $first->id));

        provider::delete_data_for_all_users_in_context($context);
        $this->assertSame([], issue_manager::get_all((int) $instance->id));
    }
}
