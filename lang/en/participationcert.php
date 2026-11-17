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
 * English language strings for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['availabilitynote'] = 'The certificate can be downloaded as soon as the activity is available to a participant. Use <em>Restrict access</em> below to set the conditions, for example completion of other activities, a grade or a date.';
$string['certificateready'] = 'Your certificate of participation is ready for download.';
$string['certificatesent'] = 'The certificate has been sent to {$a} by email.';
$string['certificatesettings'] = 'Certificate';
$string['certtitle'] = 'Title on the certificate';
$string['certtitle_help'] = 'The heading printed at the top of the certificate, for example "Certificate of participation".';
$string['code'] = 'Certificate code';
$string['dateemailed'] = 'Sent by email';
$string['dateissued'] = 'Date of issue';
$string['defaultcerttitle'] = 'Certificate of participation';
$string['deleteissueconfirm'] = 'Do you really want to delete the certificate issued to {$a}? On the next download, a new certificate with a new date and code is issued.';
$string['downloadcertificate'] = 'Download certificate';
$string['emailbody'] = 'Hello {$a->firstname},

attached you will find your certificate of participation for "{$a->course}".

You can download it again at any time on this page:
{$a->url}';
$string['emailcertificate'] = 'Send certificate by email';
$string['emailcertificate_help'] = 'If enabled, participants receive their certificate as PDF attachment by email as soon as they meet the access restrictions. The check runs after a completion or grade change of the participant, in the next cron run. Each participant receives the email once. Teachers can also send the certificates on the page of issued certificates.';
$string['emailsubject'] = 'Your certificate of participation: {$a->course}';
$string['error:emailfailed'] = 'The email to {$a} could not be sent.';
$string['error:invalidlogo'] = 'The logo uploaded in the site settings is no PNG or JPEG image.';
$string['error:noissue'] = 'No certificate has been issued to this user.';
$string['error:unknownaction'] = 'Unknown action.';
$string['eventcertificateissued'] = 'Certificate issued';
$string['eventcertificatesent'] = 'Certificate sent by email';
$string['eventissuedeleted'] = 'Issued certificate deleted';
$string['issuedcertificates'] = 'Issued certificates';
$string['issuedcertificates_help'] = 'A certificate is issued when a participant downloads it for the first time or when it is sent by email. Date of issue and code stay the same for later downloads. If you delete an issued certificate, the participant receives a new one with a new date and code.';
$string['issuedcertificatescount'] = 'Issued certificates ({$a})';
$string['issuedeleted'] = 'The certificate of {$a} has been deleted.';
$string['issuedon'] = 'Your certificate of participation was issued on {$a}.';
$string['logo'] = 'Logo on the certificate';
$string['logo_desc'] = 'PNG or JPEG image printed centred at the top of every certificate, for example the logo of your school. Without an image, the logo of Moodle in Niedersachsen e. V. is printed. A wide image with transparent background works best; it is scaled to at most 60 mm × 24 mm.';
$string['modulename'] = 'Certificate of participation';
$string['modulename_help'] = 'The certificate of participation activity lets participants download a PDF certificate that confirms their participation in the course. The certificate shows the name of the participant, the course, the scope and the contents of the course, the date of issue and a code. Use the access restrictions of the activity to decide when the certificate becomes available. Optionally, the certificate is sent by email.';
$string['modulenameplural'] = 'Certificates of participation';
$string['nocertificateforyou'] = 'Only participants of this course receive a certificate.';
$string['noissues'] = 'No certificates have been issued yet.';
$string['notavailable'] = 'The certificate is not available to you.';
$string['notavailableforuser'] = 'The certificate is currently not available to {$a}, so it was not sent.';
$string['participationcert:addinstance'] = 'Add a new certificate of participation';
$string['participationcert:manage'] = 'Send and delete issued certificates';
$string['participationcert:receive'] = 'Receive a certificate of participation';
$string['participationcert:view'] = 'View certificate of participation';
$string['participationcert:viewreport'] = 'View and download issued certificates';
$string['pdf:after'] = 'as a participant.';
$string['pdf:before'] = 'This is to certify that';
$string['pdf:between'] = 'has taken part in';
$string['pdf:code'] = 'Certificate code: {$a}';
$string['pdf:issuedon'] = 'Issued on {$a}';
$string['pdf:preview'] = 'PREVIEW';
$string['pdf:scope'] = 'Scope: {$a}';
$string['pdf:topics'] = 'Contents';
$string['pluginadministration'] = 'Certificate of participation administration';
$string['pluginname'] = 'Certificate of participation';
$string['previewcertificate'] = 'Preview certificate';
$string['previewcertificate_help'] = 'Opens the certificate with your own name, today\'s date and the current settings, so you can check layout and texts. The preview carries the watermark "PREVIEW" and a placeholder code. No certificate is issued and nothing is stored.';
$string['privacy:issue'] = 'Issued certificate';
$string['privacy:metadata:participationcert_issues'] = 'Certificates issued to users.';
$string['privacy:metadata:participationcert_issues:code'] = 'The random code printed on the certificate.';
$string['privacy:metadata:participationcert_issues:timecreated'] = 'The date of issue.';
$string['privacy:metadata:participationcert_issues:timeemailed'] = 'The time the certificate was last sent by email.';
$string['privacy:metadata:participationcert_issues:userid'] = 'The user who received the certificate.';
$string['resetissues'] = 'Delete all issued certificates';
$string['scope'] = 'Scope';
$string['scope_help'] = 'Optional. The scope or duration printed on the certificate, for example "12 lessons" or "2 days, 16 hours".';
$string['sendall'] = 'Send certificates to all participants now';
$string['sendall_help'] = 'Sends the certificate by email to every participant who meets the access restrictions and has not received it by email yet. The emails are sent in the next cron run. Use this, for example, when the certificate becomes available on a date.';
$string['sendallqueued'] = 'The certificates will be sent in the next cron run.';
$string['sendbyemail'] = 'Send by email';
$string['showlogo'] = 'Show logo';
$string['showlogo_desc'] = 'Print a logo at the top of the certificates.';
$string['tasksendcertificates'] = 'Send certificates of participation by email';
$string['topics'] = 'Contents';
$string['topics_help'] = 'Optional. The contents of the course printed on the certificate. Paragraphs, lists, bold, italic and underlined text are printed; images, links and tables are left out.';
