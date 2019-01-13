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
 * This file defines de main basicmcodelti configuration form
 *
 * @package mod_mcodelti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Charles Severance
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

class mod_mcodelti_edit_types_form extends moodleform{
    public function definition() {
        global $CFG;

        $mform    =& $this->_form;

        $istool = $this->_customdata && $this->_customdata->istool;

        // Add basicmcodelti elements.
        $mform->addElement('header', 'setup', get_string('tool_settings', 'mcodelti'));

        $mform->addElement('text', 'mcodelti_typename', get_string('typename', 'mcodelti'));
        $mform->setType('mcodelti_typename', PARAM_TEXT);
        $mform->addHelpButton('mcodelti_typename', 'typename', 'mcodelti');
        $mform->addRule('mcodelti_typename', null, 'required', null, 'client');

        $mform->addElement('text', 'mcodelti_toolurl', get_string('toolurl', 'mcodelti'), array('size' => '64'));
        $mform->setType('mcodelti_toolurl', PARAM_URL);
        $mform->addHelpButton('mcodelti_toolurl', 'toolurl', 'mcodelti');

        $mform->addElement('textarea', 'mcodelti_description', get_string('tooldescription', 'mcodelti'), array('rows' => 4, 'cols' => 60));
        $mform->setType('mcodelti_description', PARAM_TEXT);
        $mform->addHelpButton('mcodelti_description', 'tooldescription', 'mcodelti');
        if (!$istool) {
            $mform->addRule('mcodelti_toolurl', null, 'required', null, 'client');
        } else {
            $mform->disabledIf('mcodelti_toolurl', null);
        }

        if (!$istool) {
            $mform->addElement('text', 'mcodelti_resourcekey', get_string('resourcekey_admin', 'mcodelti'));
            $mform->setType('mcodelti_resourcekey', PARAM_TEXT);
            $mform->addHelpButton('mcodelti_resourcekey', 'resourcekey_admin', 'mcodelti');
            $mform->setForceLtr('mcodelti_resourcekey');

            $mform->addElement('passwordunmask', 'mcodelti_password', get_string('password_admin', 'mcodelti'));
            $mform->setType('mcodelti_password', PARAM_TEXT);
            $mform->addHelpButton('mcodelti_password', 'password_admin', 'mcodelti');
        }

        if ($istool) {
            $mform->addElement('textarea', 'mcodelti_parameters', get_string('parameter', 'mcodelti'), array('rows' => 4, 'cols' => 60));
            $mform->setType('mcodelti_parameters', PARAM_TEXT);
            $mform->addHelpButton('mcodelti_parameters', 'parameter', 'mcodelti');
            $mform->disabledIf('mcodelti_parameters', null);
            $mform->setForceLtr('mcodelti_parameters');
        }

        $mform->addElement('textarea', 'mcodelti_customparameters', get_string('custom', 'mcodelti'), array('rows' => 4, 'cols' => 60));
        $mform->setType('mcodelti_customparameters', PARAM_TEXT);
        $mform->addHelpButton('mcodelti_customparameters', 'custom', 'mcodelti');
        $mform->setForceLtr('mcodelti_customparameters');

        if (!empty($this->_customdata->isadmin)) {
            $options = array(
                mcodelti_COURSEVISIBLE_NO => get_string('show_in_course_no', 'mcodelti'),
                mcodelti_COURSEVISIBLE_PRECONFIGURED => get_string('show_in_course_preconfigured', 'mcodelti'),
                mcodelti_COURSEVISIBLE_ACTIVITYCHOOSER => get_string('show_in_course_activity_chooser', 'mcodelti'),
            );
            if ($istool) {
                // mcodelti2 tools can not be matched by URL, they have to be either in preconfigured tools or in activity chooser.
                unset($options[mcodelti_COURSEVISIBLE_NO]);
                $stringname = 'show_in_course_mcodelti2';
            } else {
                $stringname = 'show_in_course_mcodelti1';
            }
            $mform->addElement('select', 'mcodelti_coursevisible', get_string($stringname, 'mcodelti'), $options);
            $mform->addHelpButton('mcodelti_coursevisible', $stringname, 'mcodelti');
            $mform->setDefault('mcodelti_coursevisible', '1');
        } else {
            $mform->addElement('hidden', 'mcodelti_coursevisible', mcodelti_COURSEVISIBLE_PRECONFIGURED);
        }
        $mform->setType('mcodelti_coursevisible', PARAM_INT);

        $mform->addElement('hidden', 'typeid');
        $mform->setType('typeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[mcodelti_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'mcodelti');
        $launchoptions[mcodelti_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'mcodelti');
        $launchoptions[mcodelti_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'mcodelti');
        $launchoptions[mcodelti_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'mcodelti');

        $mform->addElement('select', 'mcodelti_launchcontainer', get_string('default_launch_container', 'mcodelti'), $launchoptions);
        $mform->setDefault('mcodelti_launchcontainer', mcodelti_LAUNCH_CONTAINER_EMBED_NO_BLOCKS);
        $mform->addHelpButton('mcodelti_launchcontainer', 'default_launch_container', 'mcodelti');
        $mform->setType('mcodelti_launchcontainer', PARAM_INT);

        $mform->addElement('advcheckbox', 'mcodelti_contentitem', get_string('contentitem', 'mcodelti'));
        $mform->addHelpButton('mcodelti_contentitem', 'contentitem', 'mcodelti');
        $mform->setAdvanced('mcodelti_contentitem');
        if ($istool) {
            $mform->disabledIf('mcodelti_contentitem', null);
        }

        $mform->addElement('hidden', 'oldicon');
        $mform->setType('oldicon', PARAM_URL);

        $mform->addElement('text', 'mcodelti_icon', get_string('icon_url', 'mcodelti'), array('size' => '64'));
        $mform->setType('mcodelti_icon', PARAM_URL);
        $mform->setAdvanced('mcodelti_icon');
        $mform->addHelpButton('mcodelti_icon', 'icon_url', 'mcodelti');

        $mform->addElement('text', 'mcodelti_secureicon', get_string('secure_icon_url', 'mcodelti'), array('size' => '64'));
        $mform->setType('mcodelti_secureicon', PARAM_URL);
        $mform->setAdvanced('mcodelti_secureicon');
        $mform->addHelpButton('mcodelti_secureicon', 'secure_icon_url', 'mcodelti');

        if (!$istool) {
            // Add privacy preferences fieldset where users choose whether to send their data.
            $mform->addElement('header', 'privacy', get_string('privacy', 'mcodelti'));

            $options = array();
            $options[0] = get_string('never', 'mcodelti');
            $options[1] = get_string('always', 'mcodelti');
            $options[2] = get_string('delegate', 'mcodelti');

            $mform->addElement('select', 'mcodelti_sendname', get_string('share_name_admin', 'mcodelti'), $options);
            $mform->setType('mcodelti_sendname', PARAM_INT);
            $mform->setDefault('mcodelti_sendname', '2');
            $mform->addHelpButton('mcodelti_sendname', 'share_name_admin', 'mcodelti');

            $mform->addElement('select', 'mcodelti_sendemailaddr', get_string('share_email_admin', 'mcodelti'), $options);
            $mform->setType('mcodelti_sendemailaddr', PARAM_INT);
            $mform->setDefault('mcodelti_sendemailaddr', '2');
            $mform->addHelpButton('mcodelti_sendemailaddr', 'share_email_admin', 'mcodelti');

            // mcodelti Extensions.

            // Add grading preferences fieldset where the tool is allowed to return grades.
            $mform->addElement('select', 'mcodelti_acceptgrades', get_string('accept_grades_admin', 'mcodelti'), $options);
            $mform->setType('mcodelti_acceptgrades', PARAM_INT);
            $mform->setDefault('mcodelti_acceptgrades', '2');
            $mform->addHelpButton('mcodelti_acceptgrades', 'accept_grades_admin', 'mcodelti');

            $mform->addElement('checkbox', 'mcodelti_forcessl', '&nbsp;', ' ' . get_string('force_ssl', 'mcodelti'), $options);
            $mform->setType('mcodelti_forcessl', PARAM_BOOL);
            if (!empty($CFG->mod_mcodelti_forcessl)) {
                $mform->setDefault('mcodelti_forcessl', '1');
                $mform->freeze('mcodelti_forcessl');
            } else {
                $mform->setDefault('mcodelti_forcessl', '0');
            }
            $mform->addHelpButton('mcodelti_forcessl', 'force_ssl', 'mcodelti');

            if (!empty($this->_customdata->isadmin)) {
                // Add setup parameters fieldset.
                $mform->addElement('header', 'setupoptions', get_string('miscellaneous', 'mcodelti'));

                // Adding option to change id that is placed in context_id.
                $idoptions = array();
                $idoptions[0] = get_string('id', 'mcodelti');
                $idoptions[1] = get_string('courseid', 'mcodelti');

                $mform->addElement('text', 'mcodelti_organizationid', get_string('organizationid', 'mcodelti'));
                $mform->setType('mcodelti_organizationid', PARAM_TEXT);
                $mform->addHelpButton('mcodelti_organizationid', 'organizationid', 'mcodelti');

                $mform->addElement('text', 'mcodelti_organizationurl', get_string('organizationurl', 'mcodelti'));
                $mform->setType('mcodelti_organizationurl', PARAM_URL);
                $mform->addHelpButton('mcodelti_organizationurl', 'organizationurl', 'mcodelti');
            }
        }

        /* Suppress this for now - Chuck
         * mform->addElement('text', 'mcodelti_organizationdescr', get_string('organizationdescr', 'mcodelti'))
         * mform->setType('mcodelti_organizationdescr', PARAM_TEXT)
         * mform->addHelpButton('mcodelti_organizationdescr', 'organizationdescr', 'mcodelti')
         */

        /*
        // Add a hidden element to signal a tool fixing operation after a problematic backup - restore process
        //$mform->addElement('hidden', 'mcodelti_fix');
        */

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
     * Retrieves the data of the submitted form.
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data && !empty($this->_customdata->istool)) {
            // Content item checkbox is disabled in tool settings, so this cannot be edited. Just unset it.
            unset($data->mcodelti_contentitem);
        }
        return $data;
    }
}
