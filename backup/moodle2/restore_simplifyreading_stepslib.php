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
 * Restore structure for mod_simplifyreading.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores the resource settings.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_simplifyreading_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the restore paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('simplifyreading', '/activity/simplifyreading'),
        ];
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Create the restored instance.
     *
     * @param array $data Backed up data.
     * @return void
     */
    protected function process_simplifyreading($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('simplifyreading', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore files in the description.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_simplifyreading', 'intro', null);
    }
}
