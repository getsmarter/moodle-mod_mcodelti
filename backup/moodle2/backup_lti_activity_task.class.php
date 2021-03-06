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
 * Defines backup_mcodelti_activity_task class
 *
 * @package     mod_mcodelti
 * @category    backup
 * @copyright   2009 Marc Alier <marc.alier@upc.edu>, Jordi Piguillem, Nikolas Galanis
 * @copyright   2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author      Marc Alier
 * @author      Jordi Piguillem
 * @author      Nikolas Galanis
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/mcodelti/backup/moodle2/backup_mcodelti_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the mcodelti instance
 */
class backup_mcodelti_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the mcodelti.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_mcodelti_activity_structure_step('mcodelti_structure', 'mcodelti.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of basicmcodelti tools.
        $search = "/(".$base."\/mod\/mcodelti\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@mcodeltiINDEX*$2@$', $content);

        // Link to basicmcodelti view by moduleid.
        $search = "/(".$base."\/mod\/mcodelti\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@mcodeltiVIEWBYID*$2@$', $content);

        return $content;
    }
}
