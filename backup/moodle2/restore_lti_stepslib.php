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
 * This file contains all the restore steps that will be used
 * by the restore_mcodelti_activity_task
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
 * Structure step to restore one mcodelti activity
 */
class restore_mcodelti_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $mcodelti = new restore_path_element('mcodelti', '/activity/mcodelti');
        $paths[] = $mcodelti;

        // Add support for subplugin structures.
        $this->add_subplugin_structure('mcodeltisource', $mcodelti);
        $this->add_subplugin_structure('mcodeltiservice', $mcodelti);

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_mcodelti($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->servicesalt = uniqid('', true);

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.

         // Grade used to be a float (whole numbers only), restore as int.
        $data->grade = (int) $data->grade;

        // Clean any course or site typeid. All modules
        // are restored as self-contained. Note this is
        // an interim solution until the issue below is implemented.
        // TODO: MDL-34161 - Fix restore to support course/site tools & submissions.
        $data->typeid = 0;

        // Try to decrypt resourcekey and password. Null if not possible (DB default).
        // Note these fields were originally encrypted on backup using {link @encrypted_final_element}.
        $data->resourcekey = isset($data->resourcekey) ? $this->decrypt($data->resourcekey) : null;
        $data->password = isset($data->password) ? $this->decrypt($data->password) : null;

        $newitemid = $DB->insert_record('mcodelti', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function after_execute() {
        // Add mcodelti related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_mcodelti', 'intro', null);
    }
}
