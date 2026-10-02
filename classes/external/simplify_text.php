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

namespace mod_simplifyreading\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_simplifyreading\local\simplifier;

/**
 * AJAX endpoint: rewrite pasted text at the activity's reading level.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplify_text extends external_api {
    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'text' => new external_value(PARAM_RAW, 'Text pasted by the student'),
            'attempt' => new external_value(PARAM_INT, 'Rewrite number: 1 first time, 2+ for Redo', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Rewrite the text.
     *
     * @param int $cmid Course module id.
     * @param string $text Pasted text.
     * @param int $attempt Rewrite number.
     * @return array
     */
    public static function execute(int $cmid, string $text, int $attempt = 1): array {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/mod/simplifyreading/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'text' => $text,
            'attempt' => $attempt,
        ]);

        [, $cm] = get_course_and_cm_from_cmid($params['cmid'], 'simplifyreading');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/simplifyreading:view', $context);
        require_capability('mod/simplifyreading:use', $context);

        $instance = $DB->get_record('simplifyreading', ['id' => $cm->instance], '*', MUST_EXIST);

        // Long passages can take a while on a busy AI server.
        \core_php_time_limit::raise(300);

        return simplifier::simplify($instance, $context, (int) $USER->id, $params['text'], $params['attempt']);
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the rewrite worked'),
            'html' => new external_value(PARAM_RAW, 'Rewritten text as HTML'),
            'errormessage' => new external_value(PARAM_TEXT, 'Message to show when the rewrite failed'),
        ]);
    }
}
