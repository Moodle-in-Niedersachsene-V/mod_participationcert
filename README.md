# Certificate of participation (mod_participationcert)

An activity that lets participants download a PDF certificate of participation. The teacher sets
when the certificate becomes available with Moodle's access restrictions, for example after the
participant has completed certain activities. Optionally, the certificate is sent by email.

Maintained by **Moodle in Niedersachsen e. V.**

## Features

- Fixed, clean A4 layout: logo, title, name of the participant, course, scope or duration, contents of
  the course, date of issue, site name and a random certificate code.
- Per activity: title on the certificate, scope (for example "12 lessons") and contents
  (rich text: paragraphs, lists, bold, italic, underline).
- Availability through the standard *Restrict access* settings of the activity (completion of
  other activities, grade, date, group, profile field and so on).
- Participants download their certificate from the activity page. The first download issues it;
  date of issue and code stay the same for every later download.
- Optional sending by email as PDF attachment as soon as a participant meets the restrictions.
- *Preview certificate* for teachers: opens the certificate with the teacher's own name and the
  watermark "PREVIEW"; nothing is issued or stored.
- Page *Issued certificates* for teachers: name, date of issue, code, time of the last email,
  download, sending by email and deletion of single certificates, plus a button to send the
  certificates to all participants who meet the restrictions.
- Events for issuing, sending and deleting certificates.
- Privacy API: export and deletion of issued certificates.
- Backup, restore, duplication and course reset.
- Help icons for all settings.

## Requirements

- Moodle 5.0 or later (5.x)
- PHP 8.2, 8.3 or 8.4
- MariaDB, MySQL or PostgreSQL

## Installation

1. Unpack the ZIP file into `mod/participationcert` (for Moodle 5.1 and later:
   `public/mod/participationcert`).
2. Visit *Site administration > Notifications* to complete the installation.

## Site settings

*Site administration > Plugins > Activity modules > Certificate of participation*

| Setting | Default | Meaning |
|---|---|---|
| Show logo | on | Prints a logo centred at the top of every certificate. |
| Logo on the certificate | empty | PNG or JPEG image, for example the logo of a school. Without an image, the logo of Moodle in Niedersachsen e. V. (`pix/certificatelogo.svg`) is printed. The logo is scaled to at most 60 mm × 24 mm. |

## Use

1. Add the activity *Certificate of participation* to a course.
2. Enter the title, and optionally the scope and the contents of the course.
3. Under *Restrict access*, add the conditions under which participants receive the certificate,
   for example *Activity completion* of the final activity.
4. Optionally enable *Send certificate by email*.

Participants who meet the restrictions see the button *Download certificate* on the activity page.
Teachers check layout and texts with *Preview certificate* on the same page.

## Sending by email

There is no scheduled task. Sending works with ad hoc tasks, which run with the next cron run:

- With *Send certificate by email* enabled, a completion change (activity or course) or a new grade
  of a participant queues a check. If the participant now meets the restrictions, the certificate
  is issued and sent once.
- Restrictions that depend only on a date raise no event. For these, teachers use the button
  *Send certificates to all participants now* on the page *Issued certificates*. It sends the
  certificate to all participants who meet the restrictions and have not received it by email yet.
- *Send by email* in a row of the list sends one certificate again at once.

The email comes from the no-reply address of the site and is written in the language of the
participant.

## Capabilities

| Capability | Default roles | Purpose |
|---|---|---|
| `mod/participationcert:addinstance` | Editing teacher, Manager | Add the activity |
| `mod/participationcert:view` | Student, Teacher, Editing teacher, Manager | Open the activity |
| `mod/participationcert:receive` | Student | Receive a certificate |
| `mod/participationcert:viewreport` | Teacher, Editing teacher, Manager | See and download issued certificates |
| `mod/participationcert:manage` | Editing teacher, Manager | Send and delete issued certificates |

Only active enrolments with `mod/participationcert:receive` receive a certificate. Site
administrators do not receive one through their administrator rights.

## Data and privacy

The plugin stores one record per issued certificate: user, date of issue, code and time of the
last email. The PDF itself is not stored; it is built on each download.

Issued certificates are not part of backups. Their codes are unique on the site, and a copied course
usually starts with new participants. After a restore, participants receive a new certificate with
a new date and code. A course reset can delete all issued certificates.

## Security notes

- Downloading issues the certificate, so the download only accepts POST requests with a session key.
- The contents of the course are reduced to simple text formatting before they are written into the
  PDF: images, links, tables and all attributes are removed, so the PDF library loads no files or
  URLs.
- Uploaded logos are limited to PNG and JPEG and are checked as images before printing; SVG is
  used only for the logo shipped with the plugin.
- Teachers can download only certificates that were already issued. Sending by email issues a
  certificate only to participants who meet the restrictions themselves.

## License

GNU GPL v3 or later. See <https://www.gnu.org/copyleft/gpl.html>.
