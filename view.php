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
 * This file contains all necessary code to view a mcodelti activity instance
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

require_once('../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/mod/mcodelti/lib.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$l  = optional_param('l', 0, PARAM_INT);  // mcodelti ID.
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($l) {  // Two ways to specify the module.
    $mcodelti = $DB->get_record('mcodelti', array('id' => $l), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mcodelti', $mcodelti->id, $mcodelti->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('mcodelti', $id, 0, false, MUST_EXIST);
    $mcodelti = $DB->get_record('mcodelti', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

if (!empty($mcodelti->typeid)) {
    $toolconfig = mcodelti_get_type_config($mcodelti->typeid);
} else if ($tool = mcodelti_get_tool_by_url_match($mcodelti->toolurl)) {
    $toolconfig = mcodelti_get_type_config($tool->id);
} else {
    $toolconfig = array();
}

$PAGE->set_cm($cm, $course); // Set's up global $COURSE.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

require_login($course, true, $cm);
require_capability('mod/mcodelti:view', $context);

$url = new moodle_url('/mod/mcodelti/view.php', array('id' => $cm->id));
$PAGE->set_url($url);

$launchcontainer = mcodelti_get_launch_container($mcodelti, $toolconfig);

$url = new moodle_url('/mod/mcodelti/launch.php', array('id' => $cm->id));

// // Print the page header.
 echo $OUTPUT->header();



     echo '<iframe id="contentframe" height="600px" width="100%" src="launch.php?id='.$cm->id.'&triggerview=0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';

// // Finish the page.
 echo $OUTPUT->footer();
