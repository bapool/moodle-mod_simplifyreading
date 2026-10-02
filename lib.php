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
 * Library of functions for mod_simplifyreading.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Report which Moodle features this module supports.
 *
 * Simplify Text is a resource: it has no grade, no outcomes and no group mode.
 * Access can still be limited to a group (for example NT_IEP) with the standard
 * Restrict access settings.
 *
 * @param string $feature FEATURE_xx constant for the requested feature.
 * @return mixed True or false if the feature is known, null otherwise.
 */
function simplifyreading_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        default:
            return null;
    }
}

/**
 * Grade level to Lexile ceiling map used in the AI prompt.
 *
 * Values are the lower end of the Common Core Lexile band for each grade, so
 * the AI is asked to write no harder than the start of the selected grade.
 *
 * @return array Grade number => Lexile measure.
 */
function simplifyreading_get_lexile_map(): array {
    return [
        2 => 420,
        3 => 520,
        4 => 740,
        5 => 830,
        6 => 925,
        7 => 970,
        8 => 1010,
    ];
}

/**
 * Return the Lexile ceiling for a grade, clamped to the supported range.
 *
 * @param int $grade Grade level.
 * @return int Lexile measure.
 */
function simplifyreading_get_lexile(int $grade): int {
    $map = simplifyreading_get_lexile_map();
    $grades = array_keys($map);
    $grade = max(min($grades), min(max($grades), $grade));
    return $map[$grade];
}

/**
 * Options for the grade level dropdown, e.g. "Grade 3 (Lexile 520L)".
 *
 * @return array Grade number => label.
 */
function simplifyreading_get_grade_options(): array {
    $options = [];
    foreach (simplifyreading_get_lexile_map() as $grade => $lexile) {
        $options[$grade] = get_string(
            'gradeleveloption',
            'mod_simplifyreading',
            (object) ['grade' => $grade, 'lexile' => $lexile]
        );
    }
    return $options;
}

/**
 * Add a new Simplify Text instance.
 *
 * @param stdClass $data Form data.
 * @param mod_simplifyreading_mod_form|null $mform The form.
 * @return int New instance id.
 */
function simplifyreading_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->aiinstructions = trim($data->aiinstructions ?? '');

    return $DB->insert_record('simplifyreading', $data);
}

/**
 * Update an existing Simplify Text instance.
 *
 * @param stdClass $data Form data.
 * @param mod_simplifyreading_mod_form|null $mform The form.
 * @return bool True on success.
 */
function simplifyreading_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->aiinstructions = trim($data->aiinstructions ?? '');

    return $DB->update_record('simplifyreading', $data);
}

/**
 * Delete a Simplify Text instance.
 *
 * @param int $id Instance id.
 * @return bool True on success.
 */
function simplifyreading_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('simplifyreading', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('simplifyreading', ['id' => $id]);
    return true;
}

/**
 * Information used by the course page, including the optional description.
 *
 * @param stdClass $coursemodule Course module record.
 * @return cached_cm_info|false
 */
function simplifyreading_get_coursemodule_info($coursemodule) {
    global $DB;

    $record = $DB->get_record(
        'simplifyreading',
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat'
    );
    if (!$record) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $record->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('simplifyreading', $record, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Mark the activity viewed and trigger the viewed event.
 *
 * @param stdClass $instance Instance record.
 * @param stdClass $course Course record.
 * @param cm_info|stdClass $cm Course module.
 * @param context_module $context Module context.
 * @return void
 */
function simplifyreading_view($instance, $course, $cm, $context) {
    $event = \mod_simplifyreading\event\course_module_viewed::create([
        'objectid' => $instance->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('simplifyreading', $instance);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}
