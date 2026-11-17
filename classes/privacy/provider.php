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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_participationcert.
 *
 * Personal data is the issued certificate of a user: date of issue, code and the time it was sent
 * by email. The PDF itself is not stored; it is built on each download from these data. When the
 * certificate is sent, the email with the PDF goes to the user's own address.
 *
 * @package    mod_participationcert
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the stored personal data.
     *
     * @param collection $collection Collection to add to.
     * @return collection The collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('participationcert_issues', [
            'userid' => 'privacy:metadata:participationcert_issues:userid',
            'code' => 'privacy:metadata:participationcert_issues:code',
            'timecreated' => 'privacy:metadata:participationcert_issues:timecreated',
            'timeemailed' => 'privacy:metadata:participationcert_issues:timeemailed',
        ], 'privacy:metadata:participationcert_issues');
        return $collection;
    }

    /**
     * Returns the module contexts that hold data of a user.
     *
     * @param int $userid User id.
     * @return contextlist Contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {participationcert_issues} i ON i.participationcertid = cm.instance
                 WHERE i.userid = :userid';
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'participationcert',
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Returns the users with data in a context.
     *
     * @param userlist $userlist List to add users to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = 'SELECT i.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {participationcert_issues} i ON i.participationcertid = cm.instance
                 WHERE cm.id = :cmid';
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid, 'modname' => 'participationcert']);
    }

    /**
     * Exports the issued certificates of a user.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = self::get_instance_id($context);
            if ($instanceid === null) {
                continue;
            }
            $issue = $DB->get_record('participationcert_issues', ['participationcertid' => $instanceid, 'userid' => $userid]);
            if (!$issue) {
                continue;
            }
            $data = (object) [
                'code' => $issue->code,
                'timecreated' => transform::datetime($issue->timecreated),
                'timeemailed' => $issue->timeemailed ? transform::datetime($issue->timeemailed) : '-',
            ];
            writer::with_context($context)->export_data([get_string('privacy:issue', 'participationcert')], $data);
        }
    }

    /**
     * Deletes the issued certificates of all users in a context.
     *
     * @param \context $context Context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        $instanceid = self::get_instance_id($context);
        if ($instanceid !== null) {
            $DB->delete_records('participationcert_issues', ['participationcertid' => $instanceid]);
        }
    }

    /**
     * Deletes the issued certificates of a user in the approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_users_in_context($context, [$userid]);
        }
    }

    /**
     * Deletes the issued certificates of several users in one context.
     *
     * @param approved_userlist $userlist Approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        self::delete_users_in_context($userlist->get_context(), $userlist->get_userids());
    }

    /**
     * Deletes the issued certificates of the given users in a context.
     *
     * @param \context $context Context.
     * @param int[] $userids User ids.
     * @return void
     */
    private static function delete_users_in_context(\context $context, array $userids): void {
        global $DB;

        $instanceid = self::get_instance_id($context);
        if (!$userids || $instanceid === null) {
            return;
        }
        [$usersql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['instanceid'] = $instanceid;
        $DB->delete_records_select('participationcert_issues', "participationcertid = :instanceid AND userid $usersql", $params);
    }

    /**
     * Returns the instance id of a module context, or null if it is no certificate activity.
     *
     * @param \context $context Context.
     * @return int|null Instance id.
     */
    private static function get_instance_id(\context $context): ?int {
        if (!$context instanceof \context_module) {
            return null;
        }
        $cm = get_coursemodule_from_id('participationcert', $context->instanceid);
        return $cm ? (int) $cm->instance : null;
    }
}
