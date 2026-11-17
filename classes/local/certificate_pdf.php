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

use context_module;
use stdClass;

/**
 * Builds the certificate of participation as an A4 PDF from the fixed layout.
 *
 * The PDF is drawn with Moodle's own PDF library on the server and never stored; every download
 * and every email builds it again from the stored date of issue and code. Texts are printed in the
 * current language.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_pdf {
    /** @var float Page width of A4 in millimetres. */
    private const PAGE_WIDTH = 210.0;

    /** @var float Page height of A4 in millimetres. */
    private const PAGE_HEIGHT = 297.0;

    /** @var float Side margin in millimetres. */
    private const MARGIN = 25.0;

    /** @var float Space kept free at the bottom of each page for date and code. */
    private const FOOTER_SPACE = 55.0;

    /** @var string Font that covers German umlauts. */
    private const FONT = 'freesans';

    /** @var int[] Colour of the title (dark blue). */
    private const ACCENT = [31, 56, 100];

    /** @var int[] Colour of the thick outer frame (orange of the association logo, #f98012). */
    private const FRAME_OUTER = [249, 128, 18];

    /** @var int[] Colour of the thin inner frame (#d9d4ce). */
    private const FRAME_INNER = [217, 212, 206];

    /** @var float Top edge of the logo in millimetres. */
    private const LOGO_TOP = 22.0;

    /** @var float Maximum width of the logo in millimetres. */
    private const LOGO_MAX_WIDTH = 60.0;

    /** @var float Maximum height of the logo in millimetres. */
    private const LOGO_MAX_HEIGHT = 24.0;

    /** @var string Logo of Moodle in Niedersachsen e. V., used when no other logo is uploaded. */
    public const DEFAULT_LOGO = '/mod/participationcert/pix/certificatelogo.svg';

    /** @var string Tags kept in the course contents; all attributes are removed. */
    private const ALLOWED_TAGS = '<p><br><ul><ol><li><strong><b><em><i><u><h3><h4>';

    /**
     * Returns the PDF of an issued certificate.
     *
     * @param stdClass $instance Instance record.
     * @param stdClass $course Course record.
     * @param stdClass $user Recipient with name fields.
     * @param stdClass $issue Issue record.
     * @param context_module $context Module context.
     * @param bool $preview Print a watermark that marks the PDF as preview.
     * @return string PDF content.
     */
    public static function build(
        stdClass $instance,
        stdClass $course,
        stdClass $user,
        stdClass $issue,
        context_module $context,
        bool $preview = false
    ): string {
        global $CFG, $SITE;
        require_once($CFG->libdir . '/pdflib.php');

        $plain = ['context' => $context, 'escape' => false];
        $title = self::title($instance, $context);
        $width = self::PAGE_WIDTH - 2 * self::MARGIN;

        $pdf = new \pdf('P', 'mm', 'A4');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(self::MARGIN, self::MARGIN + 10, self::MARGIN);
        $pdf->SetAutoPageBreak(true, self::FOOTER_SPACE);
        $pdf->SetTitle($title);
        $pdf->SetAuthor(format_string($SITE->fullname, true, ['escape' => false]));
        $pdf->SetCreator('Moodle mod_participationcert');
        $pdf->AddPage();
        self::frame($pdf);
        $pdf->SetY(self::logo($pdf));

        $pdf->SetTextColor(...self::ACCENT);
        $pdf->SetFont(self::FONT, 'B', \core_text::strlen($title) > 40 ? 22 : 30);
        $pdf->MultiCell($width, 0, $title, 0, 'C');
        $pdf->Ln(12);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont(self::FONT, '', 13);
        $pdf->MultiCell($width, 0, get_string('pdf:before', 'participationcert'), 0, 'C');
        $pdf->Ln(5);
        $pdf->SetFont(self::FONT, 'B', 24);
        $pdf->MultiCell($width, 0, fullname($user), 0, 'C');
        $pdf->Ln(5);
        $pdf->SetFont(self::FONT, '', 13);
        $pdf->MultiCell($width, 0, get_string('pdf:between', 'participationcert'), 0, 'C');
        $pdf->Ln(4);
        $pdf->SetFont(self::FONT, 'B', 17);
        $pdf->MultiCell($width, 0, format_string($course->fullname, true, $plain), 0, 'C');
        $pdf->Ln(4);
        $pdf->SetFont(self::FONT, '', 13);
        $pdf->MultiCell($width, 0, get_string('pdf:after', 'participationcert'), 0, 'C');
        $pdf->Ln(10);

        if ($instance->scope !== '') {
            $pdf->SetFont(self::FONT, 'B', 12);
            $pdf->MultiCell(
                $width,
                0,
                get_string('pdf:scope', 'participationcert', format_string($instance->scope, true, $plain)),
                0,
                'C'
            );
            $pdf->Ln(6);
        }

        $topics = self::clean_topics(format_text($instance->topics ?? '', $instance->topicsformat, ['context' => $context]));
        if ($topics !== '') {
            $pdf->SetFont(self::FONT, 'B', 12);
            $pdf->MultiCell($width, 0, get_string('pdf:topics', 'participationcert'), 0, 'L');
            $pdf->Ln(1);
            $pdf->SetFont(self::FONT, '', 11);
            $pdf->writeHTML($topics, true, false, true, false, '');
        }

        self::footer($pdf, $issue, $width);
        if ($preview) {
            self::watermark($pdf);
        }
        return $pdf->Output('', 'S');
    }

    /**
     * Returns the PDF a teacher sees as preview, with the teacher's name and a watermark.
     *
     * No certificate is issued and nothing is stored.
     *
     * @param stdClass $instance Instance record.
     * @param stdClass $course Course record.
     * @param stdClass $user User who requests the preview.
     * @param context_module $context Module context.
     * @return string PDF content.
     */
    public static function build_preview(stdClass $instance, stdClass $course, stdClass $user, context_module $context): string {
        $issue = (object) [
            'code' => str_repeat('X', issue_manager::CODE_LENGTH),
            'timecreated' => time(),
        ];
        return self::build($instance, $course, $user, $issue, $context, true);
    }

    /**
     * Returns the file name of the PDF for a recipient.
     *
     * @param stdClass $instance Instance record.
     * @param stdClass $user Recipient with name fields.
     * @return string File name.
     */
    public static function filename(stdClass $instance, stdClass $user): string {
        $title = $instance->certtitle !== '' ? $instance->certtitle : get_string('defaultcerttitle', 'participationcert');
        return clean_filename(format_string($title, true, ['escape' => false]) . ' ' . fullname($user) . '.pdf');
    }

    /**
     * Reduces the formatted course contents to the few tags the PDF supports.
     *
     * Images, links, tables and all attributes are removed, so the PDF library neither loads
     * files or URLs nor interprets styles given by the teacher.
     *
     * @param string $html Formatted HTML.
     * @return string Reduced HTML, empty when there is no visible text.
     */
    public static function clean_topics(string $html): string {
        $html = strip_tags($html, self::ALLOWED_TAGS);
        $html = preg_replace('/<([a-z0-9]+)\b[^>]*>/i', '<$1>', $html);
        return trim(strip_tags($html)) === '' ? '' : trim($html);
    }

    /**
     * Returns the title printed on the certificate.
     *
     * @param stdClass $instance Instance record.
     * @param context_module $context Module context.
     * @return string Title.
     */
    private static function title(stdClass $instance, context_module $context): string {
        if ($instance->certtitle === '') {
            return get_string('defaultcerttitle', 'participationcert');
        }
        return format_string($instance->certtitle, true, ['context' => $context, 'escape' => false]);
    }

    /**
     * Draws the logo centred at the top of the page.
     *
     * An image uploaded in the site settings replaces the logo of the association; the setting
     * showlogo switches the logo off.
     *
     * @param \pdf $pdf PDF document.
     * @return float Vertical position below the logo where the text starts.
     * @throws \moodle_exception When the uploaded logo is no readable image.
     */
    private static function logo(\pdf $pdf): float {
        global $CFG;

        if ((string) get_config('mod_participationcert', 'showlogo') === '0') {
            return self::MARGIN + 10;
        }

        $file = self::uploaded_logo();
        if ($file === null) {
            $path = $CFG->dirroot . self::DEFAULT_LOGO;
            [$width, $height] = self::fit(self::svg_size($path));
            $pdf->ImageSVG($path, (self::PAGE_WIDTH - $width) / 2, self::LOGO_TOP, $width, $height);
            return self::LOGO_TOP + $height + 8;
        }

        $path = make_request_directory() . '/logo';
        $file->copy_content_to($path);
        $size = getimagesize($path);
        if (!$size || !in_array($size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new \moodle_exception('error:invalidlogo', 'participationcert');
        }
        [$width, $height] = self::fit([(float) $size[0], (float) $size[1]]);
        $type = $size[2] === IMAGETYPE_PNG ? 'PNG' : 'JPG';
        $pdf->Image($path, (self::PAGE_WIDTH - $width) / 2, self::LOGO_TOP, $width, $height, $type);
        return self::LOGO_TOP + $height + 8;
    }

    /**
     * Returns the logo uploaded in the site settings, or null if there is none.
     *
     * @return \stored_file|null Logo file.
     */
    private static function uploaded_logo(): ?\stored_file {
        $files = get_file_storage()->get_area_files(
            \context_system::instance()->id,
            'mod_participationcert',
            'logo',
            0,
            'id DESC',
            false
        );
        return $files ? reset($files) : null;
    }

    /**
     * Returns the size of an SVG file from its viewBox.
     *
     * @param string $path File path.
     * @return float[] Width and height in the units of the drawing.
     * @throws \coding_exception When the file has no viewBox.
     */
    private static function svg_size(string $path): array {
        if (!preg_match('/viewBox="[\d.\-]+[ ,]+[\d.\-]+[ ,]+([\d.]+)[ ,]+([\d.]+)"/', file_get_contents($path), $matches)) {
            throw new \coding_exception('The logo ' . $path . ' has no viewBox.');
        }
        return [(float) $matches[1], (float) $matches[2]];
    }

    /**
     * Scales a size into the logo box, keeping its proportions.
     *
     * @param float[] $size Width and height in any unit.
     * @return float[] Width and height in millimetres.
     */
    private static function fit(array $size): array {
        [$width, $height] = $size;
        $scale = min(self::LOGO_MAX_WIDTH / $width, self::LOGO_MAX_HEIGHT / $height);
        return [$width * $scale, $height * $scale];
    }

    /**
     * Prints the preview watermark diagonally across every page.
     *
     * @param \pdf $pdf PDF document.
     * @return void
     */
    private static function watermark(\pdf $pdf): void {
        $text = get_string('pdf:preview', 'participationcert');
        for ($page = 1; $page <= $pdf->getNumPages(); $page++) {
            $pdf->setPage($page);
            $pdf->SetAlpha(0.15);
            $pdf->SetTextColor(200, 0, 0);
            $pdf->SetFont(self::FONT, 'B', 90);
            $pdf->StartTransform();
            $pdf->Rotate(55, self::PAGE_WIDTH / 2, self::PAGE_HEIGHT / 2);
            $pdf->SetXY(0, self::PAGE_HEIGHT / 2 - 20);
            $pdf->Cell(self::PAGE_WIDTH, 40, $text, 0, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }
    }

    /**
     * Draws a double frame on the current page.
     *
     * @param \pdf $pdf PDF document.
     * @return void
     */
    private static function frame(\pdf $pdf): void {
        $pdf->SetDrawColor(...self::FRAME_OUTER);
        $pdf->SetLineWidth(1.2);
        $pdf->Rect(10, 10, self::PAGE_WIDTH - 20, self::PAGE_HEIGHT - 20);
        $pdf->SetDrawColor(...self::FRAME_INNER);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(13, 13, self::PAGE_WIDTH - 26, self::PAGE_HEIGHT - 26);
    }

    /**
     * Prints date of issue, site name and code at the bottom of the last page.
     *
     * @param \pdf $pdf PDF document.
     * @param stdClass $issue Issue record.
     * @param float $width Text width.
     * @return void
     */
    private static function footer(\pdf $pdf, stdClass $issue, float $width): void {
        global $SITE;

        $pdf->SetAutoPageBreak(false);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont(self::FONT, '', 12);
        $pdf->SetXY(self::MARGIN, self::PAGE_HEIGHT - 50);
        $date = userdate($issue->timecreated, get_string('strftimedate', 'langconfig'));
        $pdf->MultiCell($width, 0, get_string('pdf:issuedon', 'participationcert', $date), 0, 'L');
        $pdf->Ln(1);
        $pdf->SetFont(self::FONT, '', 10);
        $pdf->MultiCell($width, 0, format_string($SITE->fullname, true, ['escape' => false]), 0, 'L');

        $pdf->SetTextColor(90, 90, 90);
        $pdf->SetFont(self::FONT, '', 9);
        $pdf->SetXY(self::MARGIN, self::PAGE_HEIGHT - 28);
        $pdf->MultiCell($width, 0, get_string('pdf:code', 'participationcert', $issue->code), 0, 'R');
    }
}
