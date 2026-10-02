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
 * Settings form for mod_simplifyreading.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/simplifyreading/lib.php');

/**
 * Settings form shown when a teacher adds or edits Simplify Text.
 *
 * @package    mod_simplifyreading
 * @copyright  2026 Brian Pool
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplifyreading_mod_form extends moodleform_mod {
    /**
     * Define the form elements.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // General section.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->setDefault('name', get_string('defaultname', 'mod_simplifyreading'));

        $this->standard_intro_elements();

        // AI settings section.
        $mform->addElement('header', 'aisettings', get_string('aisettings', 'mod_simplifyreading'));
        $mform->setExpanded('aisettings');

        $mform->addElement(
            'select',
            'gradelevel',
            get_string('gradelevel', 'mod_simplifyreading'),
            simplifyreading_get_grade_options()
        );
        $mform->setType('gradelevel', PARAM_INT);
        $mform->addHelpButton('gradelevel', 'gradelevel', 'mod_simplifyreading');
        $defaultgrade = (int) get_config('mod_simplifyreading', 'defaultgradelevel');
        $mform->setDefault('gradelevel', $defaultgrade ?: 3);

        $mform->addElement(
            'textarea',
            'aiinstructions',
            get_string('aiinstructions', 'mod_simplifyreading'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('aiinstructions', PARAM_TEXT);
        $mform->addHelpButton('aiinstructions', 'aiinstructions', 'mod_simplifyreading');
        $defaultinstructions = get_config('mod_simplifyreading', 'defaultinstructions');
        if ($defaultinstructions === false) {
            $defaultinstructions = get_string('defaultinstructions_default', 'mod_simplifyreading');
        }
        $mform->setDefault('aiinstructions', $defaultinstructions);

        // Standard course module settings, including Restrict access.
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
}
