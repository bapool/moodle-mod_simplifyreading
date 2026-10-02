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
 * Backup structure for mod_simplifyreading.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backs up the resource settings. There is no user data to back up.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_simplifyreading_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $simplifyreading = new backup_nested_element('simplifyreading', ['id'], [
            'name', 'intro', 'introformat', 'gradelevel', 'aiinstructions', 'timecreated', 'timemodified',
        ]);

        $simplifyreading->set_source_table('simplifyreading', ['id' => backup::VAR_ACTIVITYID]);
        $simplifyreading->annotate_files('mod_simplifyreading', 'intro', null);

        return $this->prepare_activity_structure($simplifyreading);
    }
}
