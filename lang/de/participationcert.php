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
 * German language strings for mod_participationcert.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['availabilitynote'] = 'Die Bescheinigung kann heruntergeladen werden, sobald die Aktivität für Teilnehmende verfügbar ist. Die Bedingungen legen Sie unten unter <em>Voraussetzungen</em> fest, zum Beispiel den Abschluss anderer Aktivitäten, eine Bewertung oder ein Datum.';
$string['certificateready'] = 'Ihre Teilnahmebescheinigung steht zum Herunterladen bereit.';
$string['certificatesent'] = 'Die Bescheinigung wurde per E-Mail an {$a} gesendet.';
$string['certificatesettings'] = 'Bescheinigung';
$string['certtitle'] = 'Titel auf der Bescheinigung';
$string['certtitle_help'] = 'Die Überschrift oben auf der Bescheinigung, zum Beispiel „Teilnahmebescheinigung“.';
$string['code'] = 'Bescheinigungs-Code';
$string['dateemailed'] = 'Per E-Mail gesendet';
$string['dateissued'] = 'Ausgestellt am';
$string['defaultcerttitle'] = 'Teilnahmebescheinigung';
$string['deleteissueconfirm'] = 'Möchten Sie die für {$a} ausgestellte Bescheinigung wirklich löschen? Beim nächsten Herunterladen wird eine neue Bescheinigung mit neuem Datum und neuem Code ausgestellt.';
$string['downloadcertificate'] = 'Bescheinigung herunterladen';
$string['emailbody'] = 'Hallo {$a->firstname},

im Anhang finden Sie Ihre Teilnahmebescheinigung für „{$a->course}“.

Sie können sie jederzeit erneut auf dieser Seite herunterladen:
{$a->url}';
$string['emailcertificate'] = 'Bescheinigung per E-Mail senden';
$string['emailcertificate_help'] = 'Wenn aktiviert, erhalten Teilnehmende ihre Bescheinigung als PDF-Anhang per E-Mail, sobald sie die Voraussetzungen erfüllen. Die Prüfung läuft nach einer Änderung des Abschlusses oder einer Bewertung im nächsten Cron-Lauf. Jede Person erhält die E-Mail einmal. Lehrkräfte können die Bescheinigungen zusätzlich auf der Seite der ausgestellten Bescheinigungen versenden.';
$string['emailsubject'] = 'Ihre Teilnahmebescheinigung: {$a->course}';
$string['error:emailfailed'] = 'Die E-Mail an {$a} konnte nicht gesendet werden.';
$string['error:invalidlogo'] = 'Das in den Website-Einstellungen hochgeladene Logo ist kein PNG- oder JPEG-Bild.';
$string['error:noissue'] = 'Für diese Person wurde keine Bescheinigung ausgestellt.';
$string['error:unknownaction'] = 'Unbekannte Aktion.';
$string['eventcertificateissued'] = 'Bescheinigung ausgestellt';
$string['eventcertificatesent'] = 'Bescheinigung per E-Mail gesendet';
$string['eventissuedeleted'] = 'Ausgestellte Bescheinigung gelöscht';
$string['issuedcertificates'] = 'Ausgestellte Bescheinigungen';
$string['issuedcertificates_help'] = 'Eine Bescheinigung wird ausgestellt, wenn Teilnehmende sie zum ersten Mal herunterladen oder wenn sie per E-Mail gesendet wird. Datum und Code bleiben bei späteren Downloads gleich. Wenn Sie eine ausgestellte Bescheinigung löschen, erhält die Person beim nächsten Mal eine neue mit neuem Datum und Code.';
$string['issuedcertificatescount'] = 'Ausgestellte Bescheinigungen ({$a})';
$string['issuedeleted'] = 'Die Bescheinigung von {$a} wurde gelöscht.';
$string['issuedon'] = 'Ihre Teilnahmebescheinigung wurde am {$a} ausgestellt.';
$string['logo'] = 'Logo auf der Bescheinigung';
$string['logo_desc'] = 'PNG- oder JPEG-Bild, das oben mittig auf jede Bescheinigung gedruckt wird, zum Beispiel das Logo Ihrer Schule. Ohne Bild wird das Logo von Moodle in Niedersachsen e. V. gedruckt. Am besten eignet sich ein Bild im Querformat mit transparentem Hintergrund; es wird auf höchstens 60 mm × 24 mm verkleinert.';
$string['modulename'] = 'Teilnahmebescheinigung';
$string['modulename_help'] = 'Mit der Aktivität Teilnahmebescheinigung können Teilnehmende eine PDF-Bescheinigung über ihre Teilnahme am Kurs herunterladen. Die Bescheinigung enthält den Namen, den Kurs, Umfang und Inhalte, das Ausstellungsdatum und einen Code. Über die Voraussetzungen der Aktivität legen Sie fest, wann die Bescheinigung verfügbar ist. Optional wird sie per E-Mail versendet.';
$string['modulenameplural'] = 'Teilnahmebescheinigungen';
$string['nocertificateforyou'] = 'Nur Teilnehmende dieses Kurses erhalten eine Bescheinigung.';
$string['noissues'] = 'Es wurden noch keine Bescheinigungen ausgestellt.';
$string['notavailable'] = 'Die Bescheinigung ist für Sie nicht verfügbar.';
$string['notavailableforuser'] = 'Die Bescheinigung ist für {$a} derzeit nicht verfügbar und wurde daher nicht gesendet.';
$string['participationcert:addinstance'] = 'Neue Teilnahmebescheinigung hinzufügen';
$string['participationcert:manage'] = 'Ausgestellte Bescheinigungen senden und löschen';
$string['participationcert:receive'] = 'Teilnahmebescheinigung erhalten';
$string['participationcert:view'] = 'Teilnahmebescheinigung ansehen';
$string['participationcert:viewreport'] = 'Ausgestellte Bescheinigungen ansehen und herunterladen';
$string['pdf:after'] = 'teilgenommen hat.';
$string['pdf:before'] = 'Hiermit wird bescheinigt, dass';
$string['pdf:between'] = 'an der Veranstaltung';
$string['pdf:code'] = 'Bescheinigungs-Code: {$a}';
$string['pdf:issuedon'] = 'Ausgestellt am {$a}';
$string['pdf:preview'] = 'VORSCHAU';
$string['pdf:scope'] = 'Umfang: {$a}';
$string['pdf:topics'] = 'Inhalte';
$string['pluginadministration'] = 'Administration Teilnahmebescheinigung';
$string['pluginname'] = 'Teilnahmebescheinigung';
$string['previewcertificate'] = 'Vorschau der Bescheinigung';
$string['previewcertificate_help'] = 'Öffnet die Bescheinigung mit Ihrem eigenen Namen, dem heutigen Datum und den aktuellen Einstellungen, damit Sie Layout und Texte prüfen können. Die Vorschau trägt das Wasserzeichen „VORSCHAU“ und einen Platzhalter-Code. Es wird keine Bescheinigung ausgestellt und nichts gespeichert.';
$string['privacy:issue'] = 'Ausgestellte Bescheinigung';
$string['privacy:metadata:participationcert_issues'] = 'An Personen ausgestellte Bescheinigungen.';
$string['privacy:metadata:participationcert_issues:code'] = 'Der zufällige Code auf der Bescheinigung.';
$string['privacy:metadata:participationcert_issues:timecreated'] = 'Das Ausstellungsdatum.';
$string['privacy:metadata:participationcert_issues:timeemailed'] = 'Der Zeitpunkt, an dem die Bescheinigung zuletzt per E-Mail gesendet wurde.';
$string['privacy:metadata:participationcert_issues:userid'] = 'Die Person, die die Bescheinigung erhalten hat.';
$string['resetissues'] = 'Alle ausgestellten Bescheinigungen löschen';
$string['scope'] = 'Umfang';
$string['scope_help'] = 'Optional. Der Umfang oder die Dauer, die auf der Bescheinigung steht, zum Beispiel „12 Unterrichtsstunden“ oder „2 Tage, 16 Zeitstunden“.';
$string['sendall'] = 'Bescheinigungen jetzt an alle Teilnehmenden senden';
$string['sendall_help'] = 'Sendet die Bescheinigung per E-Mail an alle Teilnehmenden, die die Voraussetzungen erfüllen und sie noch nicht per E-Mail erhalten haben. Die E-Mails werden im nächsten Cron-Lauf versendet. Das ist zum Beispiel nützlich, wenn die Bescheinigung ab einem Datum verfügbar ist.';
$string['sendallqueued'] = 'Die Bescheinigungen werden im nächsten Cron-Lauf versendet.';
$string['sendbyemail'] = 'Per E-Mail senden';
$string['showlogo'] = 'Logo anzeigen';
$string['showlogo_desc'] = 'Druckt ein Logo oben auf die Bescheinigungen.';
$string['tasksendcertificates'] = 'Teilnahmebescheinigungen per E-Mail senden';
$string['topics'] = 'Inhalte';
$string['topics_help'] = 'Optional. Die Inhalte des Kurses, die auf der Bescheinigung stehen. Absätze, Listen sowie fetter, kursiver und unterstrichener Text werden gedruckt; Bilder, Links und Tabellen werden weggelassen.';
