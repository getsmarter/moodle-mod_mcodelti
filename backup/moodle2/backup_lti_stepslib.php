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
 * This file contains all the backup steps that will be used
 * by the backup_mcodelti_activity_task
 *
 * @package mod_mcodelti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete assignment structure for backup, with file and id annotations
 */
class backup_mcodelti_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // TODO: MDL-34161 - Fix restore to support course/site tools & submissions.

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $mcodelti = new backup_nested_element('mcodelti', array('id'), array(
            'name',
            'intro',
            'introformat',
            'timecreated',
            'timemodified',
            'typeid',
            'toolurl',
            'securetoolurl',
            'preferheight',
            'launchcontainer',
            'instructorchoicesendname',
            'instructorchoicesendemailaddr',
            'instructorchoiceacceptgrades',
            'instructorchoiceallowroster',
            'instructorchoiceallowsetting',
            'grade',
            'instructorcustomparameters',
            'debuglaunch',
            'showtitlelaunch',
            'showdescriptionlaunch',
            'icon',
            'secureicon',
            new encrypted_final_element('resourcekey'),
            new encrypted_final_element('password'),
            )
        );

        // Build the tree
        // (none).

        // Define sources.
        $mcodelti->set_source_table('mcodelti', array('id' => backup::VAR_ACTIVITYID));

        // Define id annotations
        // (none).

        // Define file annotations.
        $mcodelti->annotate_files('mod_mcodelti', 'intro', null); // This file areas haven't itemid.

        // Add support for subplugin structures.
        $this->add_subplugin_structure('mcodeltisource', $mcodelti, true);
        $this->add_subplugin_structure('mcodeltiservice', $mcodelti, true);

        // Return the root element (mcodelti), wrapped into standard activity structure.
        return $this->prepare_activity_structure($mcodelti);
    }
}
