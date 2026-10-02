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
 * Restore task for mod_simplifyreading.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simplifyreading/backup/moodle2/restore_simplifyreading_stepslib.php');

/**
 * Restore task for a Simplify Text resource.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_simplifyreading_activity_task extends restore_activity_task {
    /**
     * No special settings.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Add the structure step.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_simplifyreading_activity_structure_step(
            'simplifyreading_structure',
            'simplifyreading.xml'
        ));
    }

    /**
     * Content fields that may contain encoded links.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('simplifyreading', ['intro'], 'simplifyreading'),
        ];
    }

    /**
     * Rules for decoding links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('SIMPLIFYREADINGVIEWBYID', '/mod/simplifyreading/view.php?id=$1', 'course_module'),
            new restore_decode_rule('SIMPLIFYREADINGINDEX', '/mod/simplifyreading/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Legacy log rules for the activity.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('simplifyreading', 'add', 'view.php?id={course_module}', '{simplifyreading}'),
            new restore_log_rule('simplifyreading', 'update', 'view.php?id={course_module}', '{simplifyreading}'),
            new restore_log_rule('simplifyreading', 'view', 'view.php?id={course_module}', '{simplifyreading}'),
        ];
    }

    /**
     * Legacy log rules for the course.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('simplifyreading', 'view all', 'index.php?id={course}', null),
        ];
    }
}
