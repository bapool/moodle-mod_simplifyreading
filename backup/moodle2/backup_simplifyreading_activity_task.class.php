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
 * Backup task for mod_simplifyreading.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simplifyreading/backup/moodle2/backup_simplifyreading_stepslib.php');

/**
 * Backup task for a Simplify Text resource.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_simplifyreading_activity_task extends backup_activity_task {
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
        $this->add_step(new backup_simplifyreading_activity_structure_step(
            'simplifyreading_structure',
            'simplifyreading.xml'
        ));
    }

    /**
     * Encode links to this module so they survive restore.
     *
     * @param string $content Content to encode.
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/simplifyreading\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@SIMPLIFYREADINGINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/simplifyreading\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@SIMPLIFYREADINGVIEWBYID*$2@$', $content);

        return $content;
    }
}
