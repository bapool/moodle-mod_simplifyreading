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

namespace mod_simplifyreading\event;

/**
 * Event fired each time a student gets a simplified rewrite.
 *
 * Only the word count and rewrite number are logged, never the text itself.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @property-read array $other {
 *      - int wordcount: number of words the student pasted
 *      - int attempt: 1 for the first rewrite, 2+ for Redo
 * }
 */
class text_simplified extends \core\event\base {
    /**
     * Initialise the event data.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'simplifyreading';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtextsimplified', 'mod_simplifyreading');
    }

    /**
     * Non-localised description for the logs.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' simplified {$this->other['wordcount']} words " .
            "(version {$this->other['attempt']}) in the Simplify Text resource with course module id " .
            "'{$this->contextinstanceid}'.";
    }

    /**
     * Link to the resource.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/simplifyreading/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Check required data is present.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['wordcount'])) {
            throw new \coding_exception('The \'wordcount\' value must be set in other.');
        }
        if (!isset($this->other['attempt'])) {
            throw new \coding_exception('The \'attempt\' value must be set in other.');
        }
    }

    /**
     * Mapping used by backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'simplifyreading', 'restore' => 'simplifyreading'];
    }

    /**
     * No ids in 'other' need mapping.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
