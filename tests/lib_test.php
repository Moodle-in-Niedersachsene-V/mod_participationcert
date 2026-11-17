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
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for the library callbacks: instance settings, deletion and course reset.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('participationcert_add_instance')]
#[CoversFunction('participationcert_delete_instance')]
#[CoversFunction('participationcert_reset_userdata')]
final class lib_test extends \advanced_testcase {
    /**
     * Loads the library callbacks.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/mod/participationcert/lib.php');
    }

    public function test_add_instance_stores_editor_contents_and_trimmed_fields(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('participationcert', [
            'course' => $course->id,
            'certtitle' => '  Zertifikat  ',
            'scope' => ' 8 Stunden ',
            'topicseditor' => ['text' => '<p>Thema</p>', 'format' => FORMAT_HTML],
            'emailcertificate' => 1,
        ]);

        $record = $DB->get_record('participationcert', ['id' => $instance->id]);
        $this->assertSame('Zertifikat', $record->certtitle);
        $this->assertSame('8 Stunden', $record->scope);
        $this->assertSame('<p>Thema</p>', $record->topics);
        $this->assertSame(1, (int) $record->emailcertificate);
    }

    public function test_delete_instance_removes_issues(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        issue_manager::issue($instance, \context_module::instance($instance->cmid), (int) $student->id);

        $this->assertTrue(participationcert_delete_instance($instance->id));

        $this->assertFalse($DB->record_exists('participationcert', ['id' => $instance->id]));
        $this->assertFalse($DB->record_exists('participationcert_issues', ['participationcertid' => $instance->id]));
        $this->assertFalse(participationcert_delete_instance($instance->id));
    }

    public function test_course_reset_deletes_issues_only_when_selected(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        issue_manager::issue($instance, \context_module::instance($instance->cmid), (int) $student->id);

        $this->assertSame([], participationcert_reset_userdata((object) ['courseid' => $course->id]));
        $this->assertCount(1, issue_manager::get_all((int) $instance->id));

        $status = participationcert_reset_userdata((object) ['courseid' => $course->id, 'reset_participationcert_issues' => 1]);
        $this->assertFalse($status[0]['error']);
        $this->assertSame([], issue_manager::get_all((int) $instance->id));
    }
}
