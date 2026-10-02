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
 * Student page for a Simplify Text resource.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'simplifyreading');
$instance = $DB->get_record('simplifyreading', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simplifyreading:view', $context);

simplifyreading_view($instance, $course, $cm, $context);

$PAGE->set_url('/mod/simplifyreading/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

$canmanage = has_capability('moodle/course:manageactivities', $context);

if (has_capability('mod/simplifyreading:use', $context)) {
    $maxwords = (int) get_config('mod_simplifyreading', 'maxwords');
    if ($maxwords <= 0) {
        $maxwords = 3000;
    }
    $grade = (int) $instance->gradelevel;
    $rootid = html_writer::random_id('mod_simplifyreading');

    $templatedata = [
        'rootid' => $rootid,
        'cmid' => $cm->id,
        'maxwords' => $maxwords,
        'canmanage' => $canmanage,
        'gradelabel' => get_string(
            'gradeleveloption',
            'mod_simplifyreading',
            (object) ['grade' => $grade, 'lexile' => simplifyreading_get_lexile($grade)]
        ),
    ];
    echo $OUTPUT->render_from_template('mod_simplifyreading/view', $templatedata);
    $PAGE->requires->js_call_amd('mod_simplifyreading/simplify', 'init', [$rootid]);
} else {
    echo $OUTPUT->notification(get_string('nopermission', 'mod_simplifyreading'), 'info');
}

echo $OUTPUT->footer();
