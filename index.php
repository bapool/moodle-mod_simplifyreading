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
 * Lists all Simplify Text resources in a course.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$event = \mod_simplifyreading\event\course_module_instance_list_viewed::create(['context' => $coursecontext]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strplural = get_string('modulenameplural', 'mod_simplifyreading');

$PAGE->set_url('/mod/simplifyreading/index.php', ['id' => $course->id]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($course->shortname) . ': ' . $strplural);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($strplural);

echo $OUTPUT->header();
echo $OUTPUT->heading($strplural);

$instances = get_all_instances_in_course('simplifyreading', $course);
if (empty($instances)) {
    notice(get_string('thereareno', 'moodle', $strplural), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
if ($usesections) {
    $table->head = [get_string('sectionname', 'format_' . $course->format), get_string('name'), get_string('description')];
} else {
    $table->head = [get_string('name'), get_string('description')];
}

foreach ($instances as $instance) {
    $attributes = $instance->visible ? [] : ['class' => 'dimmed'];
    $link = html_writer::link(
        new moodle_url('/mod/simplifyreading/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name),
        $attributes
    );
    $intro = format_module_intro('simplifyreading', $instance, $instance->coursemodule);

    if ($usesections) {
        $table->data[] = [get_section_name($course, $instance->section), $link, $intro];
    } else {
        $table->data[] = [$link, $intro];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
