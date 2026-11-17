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
 * Tests for the certificate PDF.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(certificate_pdf::class)]
final class certificate_pdf_test extends \advanced_testcase {
    public function test_build_returns_a_one_page_pdf_and_a_named_file(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Fortbildung Ä&Ö']);
        $student = $generator->create_and_enrol($course, 'student', ['firstname' => 'Jörg', 'lastname' => 'Müller']);
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        $context = \context_module::instance($instance->cmid);
        $issue = issue_manager::issue($instance, $context, (int) $student->id);

        $pdf = certificate_pdf::build($instance, $course, $student, $issue, $context);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(1, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
        $this->assertSame('Teilnahmebescheinigung Jörg Müller.pdf', certificate_pdf::filename($instance, $student));
    }

    public function test_long_contents_continue_on_a_second_page(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $items = str_repeat('<li>Ein Thema mit einer etwas längeren Beschreibung</li>', 60);
        $instance = $generator->create_module('participationcert', [
            'course' => $course->id,
            'topicseditor' => ['text' => "<ul>$items</ul>", 'format' => FORMAT_HTML],
        ]);
        $context = \context_module::instance($instance->cmid);
        $issue = issue_manager::issue($instance, $context, (int) $student->id);

        $pdf = certificate_pdf::build($instance, $course, $student, $issue, $context);

        $this->assertGreaterThanOrEqual(2, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }

    public function test_preview_has_a_watermark_and_issues_nothing(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        $context = \context_module::instance($instance->cmid);

        $preview = certificate_pdf::build_preview($instance, $course, $teacher, $context);

        $this->assertStringStartsWith('%PDF-', $preview);
        $this->assertMatchesRegularExpression('#/ca 0\.15#', $preview);
        $this->assertSame(0, $DB->count_records('participationcert_issues'));
    }

    public function test_clean_topics_keeps_text_formatting_and_drops_images_links_and_attributes(): void {
        $html = '<p style="color:red" onclick="x()">Intro <img src="file:///etc/passwd"> <a href="https://example.org">Link</a></p>'
            . '<table><tr><td>Zelle</td></tr></table><ul class="x"><li><strong>Fett</strong></li></ul>';

        $clean = certificate_pdf::clean_topics($html);

        $this->assertSame('<p>Intro  Link</p>Zelle<ul><li><strong>Fett</strong></li></ul>', $clean);
        $this->assertSame('', certificate_pdf::clean_topics('<p> </p><img src="x.png">'));
    }

    public function test_logo_is_the_association_logo_an_uploaded_image_or_switched_off(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        $context = \context_module::instance($instance->cmid);
        $issue = issue_manager::issue($instance, $context, (int) $student->id);

        set_config('showlogo', 0, 'mod_participationcert');
        $withoutlogo = certificate_pdf::build($instance, $course, $student, $issue, $context);
        set_config('showlogo', 1, 'mod_participationcert');
        $defaultlogo = certificate_pdf::build($instance, $course, $student, $issue, $context);
        $this->store_logo('logo.png', $this->png());
        $uploadedlogo = certificate_pdf::build($instance, $course, $student, $issue, $context);

        $this->assertStringNotContainsString('/Subtype /Image', $withoutlogo);
        $this->assertStringNotContainsString('/Subtype /Image', $defaultlogo);
        $this->assertGreaterThan(strlen($withoutlogo) + 1000, strlen($defaultlogo));
        $this->assertStringContainsString('/Subtype /Image', $uploadedlogo);
    }

    public function test_uploaded_logo_that_is_no_image_fails_loudly(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        $instance = $generator->create_module('participationcert', ['course' => $course->id]);
        $context = \context_module::instance($instance->cmid);
        $issue = issue_manager::issue($instance, $context, (int) $student->id);
        $this->store_logo('logo.png', 'not an image');

        $this->expectException(\moodle_exception::class);
        certificate_pdf::build($instance, $course, $student, $issue, $context);
    }

    /**
     * Stores a file in the logo area of the site settings.
     *
     * @param string $filename File name.
     * @param string $content File content.
     * @return void
     */
    private function store_logo(string $filename, string $content): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'mod_participationcert',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
    }

    /**
     * Returns a small orange PNG image.
     *
     * @return string PNG content.
     */
    private function png(): string {
        $image = imagecreatetruecolor(200, 80);
        imagefill($image, 0, 0, imagecolorallocate($image, 249, 128, 18));
        ob_start();
        imagepng($image);
        return ob_get_clean();
    }
}
