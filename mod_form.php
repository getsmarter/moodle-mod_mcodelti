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
//
// This file is part of Basicmcodelti4Moodle
//
// Basicmcodelti4Moodle is an IMS Basicmcodelti (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. Basicmcodelti is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS Basicmcodelti
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support Basicmcodelti. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// Basicmcodelti4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// Simplemcodelti consumer for Moodle is an implementation of the early specification of mcodelti
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// Basicmcodelti4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file defines the main mcodelti configuration form
 *
 * @package mod_mcodelti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

class mod_mcodelti_mod_form extends moodleform_mod {

    public function definition() {
        global $PAGE, $OUTPUT, $COURSE;

        if ($type = optional_param('type', false, PARAM_ALPHA)) {
            component_callback("mcodeltisource_$type", 'add_instance_hook');
        }

        $this->typeid = 0;

        $mform =& $this->_form;
        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('basicmcodeltiname', 'mcodelti'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // Adding the optional "intro" and "introformat" pair of fields.
        $this->standard_intro_elements(get_string('basicmcodeltiintro', 'mcodelti'));
        $mform->setAdvanced('introeditor');

        // Display the label to the right of the checkbox so it looks better & matches rest of the form.
        if ($mform->elementExists('showdescription')) {
            $coursedesc = $mform->getElement('showdescription');
            if (!empty($coursedesc)) {
                $coursedesc->setText(' ' . $coursedesc->getLabel());
                $coursedesc->setLabel('&nbsp');
            }
        }

        $mform->setAdvanced('showdescription');

        $mform->addElement('checkbox', 'showtitlelaunch', '&nbsp;', ' ' . get_string('display_name', 'mcodelti'));
        $mform->setAdvanced('showtitlelaunch');
        $mform->setDefault('showtitlelaunch', true);
        $mform->addHelpButton('showtitlelaunch', 'display_name', 'mcodelti');

        $mform->addElement('checkbox', 'showdescriptionlaunch', '&nbsp;', ' ' . get_string('display_description', 'mcodelti'));
        $mform->setAdvanced('showdescriptionlaunch');
        $mform->addHelpButton('showdescriptionlaunch', 'display_description', 'mcodelti');

        // Tool settings.
        $tooltypes = $mform->addElement('select', 'typeid', get_string('external_tool_type', 'mcodelti'));
        // Type ID parameter being passed when adding an preconfigured tool from activity chooser.
        $typeid = optional_param('typeid', false, PARAM_INT);
        if ($typeid) {
            $mform->getElement('typeid')->setValue($typeid);
        }
        $mform->addHelpButton('typeid', 'external_tool_type', 'mcodelti');
        $toolproxy = array();

        // Array of tool type IDs that don't support ContentItemSelectionRequest.
        $noncontentitemtypes = [];

        foreach (mcodelti_get_types_for_add_instance() as $id => $type) {
            if (!empty($type->toolproxyid)) {
                $toolproxy[] = $type->id;
                $attributes = array( 'globalTool' => 1, 'toolproxy' => 1);
                $enabledcapabilities = explode("\n", $type->enabledcapability);
                if (!in_array('Result.autocreate', $enabledcapabilities)) {
                    $attributes['nogrades'] = 1;
                }
                if (!in_array('Person.name.full', $enabledcapabilities) && !in_array('Person.name.family', $enabledcapabilities) &&
                    !in_array('Person.name.given', $enabledcapabilities)) {
                    $attributes['noname'] = 1;
                }
                if (!in_array('Person.email.primary', $enabledcapabilities)) {
                    $attributes['noemail'] = 1;
                }
            } else if ($type->course == $COURSE->id) {
                $attributes = array( 'editable' => 1, 'courseTool' => 1, 'domain' => $type->tooldomain );
            } else if ($id != 0) {
                $attributes = array( 'globalTool' => 1, 'domain' => $type->tooldomain);
            } else {
                $attributes = array();
            }

            if ($id) {
                $config = mcodelti_get_type_config($id);
                if (!empty($config['contentitem'])) {
                    $attributes['data-contentitem'] = 1;
                    $attributes['data-id'] = $id;
                } else {
                    $noncontentitemtypes[] = $id;
                }
            }
            $tooltypes->addOption($type->name, $id, $attributes);
        }

        // Add button that launches the content-item selection dialogue.
        // Set contentitem URL.
        $contentitemurl = new moodle_url('/mod/mcodelti/contentitem.php');
        $contentbuttonattributes = [
            'data-contentitemurl' => $contentitemurl->out(false)
        ];
        $contentbuttonlabel = get_string('selectcontent', 'mcodelti');
        $contentbutton = $mform->addElement('button', 'selectcontent', $contentbuttonlabel, $contentbuttonattributes);
        // Disable select content button if the selected tool doesn't support content item or it's set to Automatic.
        $allnoncontentitemtypes = $noncontentitemtypes;
        $allnoncontentitemtypes[] = '0'; // Add option value for "Automatic, based on tool URL".
        $mform->disabledIf('selectcontent', 'typeid', 'in', $allnoncontentitemtypes);

        $mform->addElement('text', 'toolurl', get_string('launch_url', 'mcodelti'), array('size' => '64'));
        $mform->setType('toolurl', PARAM_URL);
        $mform->addHelpButton('toolurl', 'launch_url', 'mcodelti');
        $mform->disabledIf('toolurl', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('text', 'securetoolurl', get_string('secure_launch_url', 'mcodelti'), array('size' => '64'));
        $mform->setType('securetoolurl', PARAM_URL);
        $mform->setAdvanced('securetoolurl');
        $mform->addHelpButton('securetoolurl', 'secure_launch_url', 'mcodelti');
        $mform->disabledIf('securetoolurl', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('hidden', 'urlmatchedtypeid', '', array( 'id' => 'id_urlmatchedtypeid' ));
        $mform->setType('urlmatchedtypeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[MCODELTI_LAUNCH_CONTAINER_DEFAULT] = get_string('default', 'mcodelti');
        $launchoptions[MCODELTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'mcodelti');
        $launchoptions[MCODELTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'mcodelti');
        $launchoptions[MCODELTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'mcodelti');
        $launchoptions[MCODELTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'mcodelti');

        $mform->addElement('select', 'launchcontainer', get_string('launchinpopup', 'mcodelti'), $launchoptions);
        $mform->setDefault('launchcontainer', MCODELTI_LAUNCH_CONTAINER_DEFAULT);
        $mform->addHelpButton('launchcontainer', 'launchinpopup', 'mcodelti');
        $mform->setAdvanced('launchcontainer');

        $mform->addElement('text', 'resourcekey', get_string('resourcekey', 'mcodelti'));
        $mform->setType('resourcekey', PARAM_TEXT);
        $mform->setAdvanced('resourcekey');
        $mform->addHelpButton('resourcekey', 'resourcekey', 'mcodelti');
        $mform->setForceLtr('resourcekey');
        $mform->disabledIf('resourcekey', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('passwordunmask', 'password', get_string('password', 'mcodelti'));
        $mform->setType('password', PARAM_TEXT);
        $mform->setAdvanced('password');
        $mform->addHelpButton('password', 'password', 'mcodelti');
        $mform->disabledIf('password', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('textarea', 'instructorcustomparameters', get_string('custom', 'mcodelti'), array('rows' => 4, 'cols' => 60));
        $mform->setType('instructorcustomparameters', PARAM_TEXT);
        $mform->setAdvanced('instructorcustomparameters');
        $mform->addHelpButton('instructorcustomparameters', 'custom', 'mcodelti');
        $mform->setForceLtr('instructorcustomparameters');

        $mform->addElement('text', 'icon', get_string('icon_url', 'mcodelti'), array('size' => '64'));
        $mform->setType('icon', PARAM_URL);
        $mform->setAdvanced('icon');
        $mform->addHelpButton('icon', 'icon_url', 'mcodelti');
        $mform->disabledIf('icon', 'typeid', 'in', $noncontentitemtypes);

        $mform->addElement('text', 'secureicon', get_string('secure_icon_url', 'mcodelti'), array('size' => '64'));
        $mform->setType('secureicon', PARAM_URL);
        $mform->setAdvanced('secureicon');
        $mform->addHelpButton('secureicon', 'secure_icon_url', 'mcodelti');
        $mform->disabledIf('secureicon', 'typeid', 'in', $noncontentitemtypes);

        // Add privacy preferences fieldset where users choose whether to send their data.
        $mform->addElement('header', 'privacy', get_string('privacy', 'mcodelti'));

        $mform->addElement('advcheckbox', 'instructorchoicesendname', '&nbsp;', ' ' . get_string('share_name', 'mcodelti'));
        $mform->setDefault('instructorchoicesendname', '1');
        $mform->addHelpButton('instructorchoicesendname', 'share_name', 'mcodelti');
        $mform->disabledIf('instructorchoicesendname', 'typeid', 'in', $toolproxy);

        $mform->addElement('advcheckbox', 'instructorchoicesendemailaddr', '&nbsp;', ' ' . get_string('share_email', 'mcodelti'));
        $mform->setDefault('instructorchoicesendemailaddr', '1');
        $mform->addHelpButton('instructorchoicesendemailaddr', 'share_email', 'mcodelti');
        $mform->disabledIf('instructorchoicesendemailaddr', 'typeid', 'in', $toolproxy);

        $mform->addElement('advcheckbox', 'instructorchoiceacceptgrades', '&nbsp;', ' ' . get_string('accept_grades', 'mcodelti'));
        $mform->setDefault('instructorchoiceacceptgrades', '1');
        $mform->addHelpButton('instructorchoiceacceptgrades', 'accept_grades', 'mcodelti');
        $mform->disabledIf('instructorchoiceacceptgrades', 'typeid', 'in', $toolproxy);

        // Add standard course module grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        $mform->setAdvanced('cmidnumber');

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

        $editurl = new moodle_url('/mod/mcodelti/instructor_edit_tool_type.php',
                array('sesskey' => sesskey(), 'course' => $COURSE->id));
        $ajaxurl = new moodle_url('/mod/mcodelti/ajax.php');

        $module = array(
            'name' => 'mod_mcodelti_edit',
            'fullpath' => '/mod/mcodelti/mod_form.js',
            'requires' => array('base', 'io', 'querystring-stringify-simple', 'node', 'event', 'json-parse'),
            'strings' => array(
                array('addtype', 'mcodelti'),
                array('edittype', 'mcodelti'),
                array('deletetype', 'mcodelti'),
                array('delete_confirmation', 'mcodelti'),
                array('cannot_edit', 'mcodelti'),
                array('cannot_delete', 'mcodelti'),
                array('global_tool_types', 'mcodelti'),
                array('course_tool_types', 'mcodelti'),
                array('using_tool_configuration', 'mcodelti'),
                array('using_tool_cartridge', 'mcodelti'),
                array('domain_mismatch', 'mcodelti'),
                array('custom_config', 'mcodelti'),
                array('tool_config_not_found', 'mcodelti'),
                array('tooltypeadded', 'mcodelti'),
                array('tooltypedeleted', 'mcodelti'),
                array('tooltypenotdeleted', 'mcodelti'),
                array('tooltypeupdated', 'mcodelti'),
                array('forced_help', 'mcodelti')
            ),
        );

        if (!empty($typeid)) {
            $mform->setAdvanced('typeid');
            $mform->setAdvanced('toolurl');
        }

        $PAGE->requires->js_init_call('M.mod_mcodelti.editor.init', array(json_encode($jsinfo)), true, $module);
    }

}
