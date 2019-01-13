<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines the main tool registration configuration form
 *
 * @package mod_mcodelti
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

/**
 * The mod_mcodelti_register_types_form class.
 *
 * @package    mod_mcodelti
 * @since      Moodle 2.8
 * @copyright  2014 Vital Source Technologies http://vitalsource.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mcodelti_register_types_form extends moodleform {

    /**
     * Set up the form definition.
     */
    public function definition() {
        global $CFG;

        $mform    =& $this->_form;

        $mform->addElement('header', 'setup', get_string('registration_options', 'mcodelti'));

        // Tool Provider name.

        $strrequired = get_string('required');
        $mform->addElement('text', 'mcodelti_registrationname', get_string('registrationname', 'mcodelti'));
        $mform->setType('mcodelti_registrationname', PARAM_TEXT);
        $mform->addHelpButton('mcodelti_registrationname', 'registrationname', 'mcodelti');
        $mform->addRule('mcodelti_registrationname', $strrequired, 'required', null, 'client');

        // Registration URL.

        $mform->addElement('text', 'mcodelti_registrationurl', get_string('registrationurl', 'mcodelti'), array('size' => '64'));
        $mform->setType('mcodelti_registrationurl', PARAM_URL);
        $mform->addHelpButton('mcodelti_registrationurl', 'registrationurl', 'mcodelti');
        $mform->addRule('mcodelti_registrationurl', $strrequired, 'required', null, 'client');

        // mcodelti Capabilities.

        $options = array_keys(mcodelti_get_capabilities());
        natcasesort($options);
        $attributes = array( 'mumcodeltiple' => 1, 'size' => min(count($options), 10) );
        $mform->addElement('select', 'mcodelti_capabilities', get_string('capabilities', 'mcodelti'),
            array_combine($options, $options), $attributes);
        $mform->setType('mcodelti_capabilities', PARAM_TEXT);
        $mform->addHelpButton('mcodelti_capabilities', 'capabilities', 'mcodelti');
        $mform->addRule('mcodelti_capabilities', $strrequired, 'required', null, 'client');

        // mcodelti Services.

        $services = mcodelti_get_services();
        $options = array();
        foreach ($services as $service) {
            $options[$service->get_id()] = $service->get_name();
        }
        $attributes = array( 'mumcodeltiple' => 1, 'size' => min(count($options), 10) );
        $mform->addElement('select', 'mcodelti_services', get_string('services', 'mcodelti'), $options, $attributes);
        $mform->setType('mcodelti_services', PARAM_TEXT);
        $mform->addHelpButton('mcodelti_services', 'services', 'mcodelti');
        $mform->addRule('mcodelti_services', $strrequired, 'required', null, 'client');

        $mform->addElement('hidden', 'toolproxyid');
        $mform->setType('toolproxyid', PARAM_INT);

        $tab = optional_param('tab', '', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'tab', $tab);
        $mform->setType('tab', PARAM_ALPHAEXT);

        $courseid = optional_param('course', 1, PARAM_INT);
        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        // Add standard buttons, common to all modules.

        $this->add_action_buttons();
    }

    /**
     * Set up rules for disabling fields.
     */
    public function disable_fields() {

        $mform    =& $this->_form;

        $mform->disabledIf('mcodelti_registrationurl', null);
        $mform->disabledIf('mcodelti_capabilities', null);
        $mform->disabledIf('mcodelti_services', null);

    }
}
