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
 * Handle the return back to Moodle from the tool provider
 *
 * @package mod_mcodelti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/mcodelti/lib.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

$courseid = required_param('course', PARAM_INT);
$instanceid = optional_param('instanceid', 0, PARAM_INT);

$errormsg = optional_param('mcodelti_errormsg', '', PARAM_TEXT);
$msg = optional_param('mcodelti_msg', '', PARAM_TEXT);
$unsigned = optional_param('unsigned', '0', PARAM_INT);

$launchcontainer = optional_param('launch_container', mcodelti_LAUNCH_CONTAINER_WINDOW, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$mcodelti = null;
$context = null;
if (!empty($instanceid)) {
    $mcodelti = $DB->get_record('mcodelti', array('id' => $instanceid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mcodelti', $mcodelti->id, $mcodelti->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
}


require_login($course);
require_sesskey();

if (!empty($errormsg) || !empty($msg)) {
    $url = new moodle_url('/mod/mcodelti/return.php', array('course' => $courseid));
    $PAGE->set_url($url);

    $pagetitle = strip_tags($course->shortname);
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);

    // Avoid frame-in-frame action.
    if ($launchcontainer == mcodelti_LAUNCH_CONTAINER_EMBED || $launchcontainer == mcodelti_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
        $PAGE->set_pagelayout('embedded');
    } else {
        $PAGE->set_pagelayout('incourse');
    }

    echo $OUTPUT->header();
    if (!empty($mcodelti) and !empty($context)) {
        echo $OUTPUT->heading(format_string($mcodelti->name, true, array('context' => $context)));
    }
}

if (!empty($errormsg)) {
    echo get_string('mcodelti_launch_error', 'mcodelti');

    p($errormsg);

    if ($unsigned == 1) {

        $contextcourse = context_course::instance($courseid);
        echo '<br /><br />';
        $links = new stdClass();

        if (has_capability('mod/mcodelti:addcoursetool', $contextcourse)) {
            $coursetooleditor = new moodle_url('/mod/mcodelti/instructor_edit_tool_type.php',
                array('course' => $courseid, 'action' => 'add', 'sesskey' => sesskey()));
            $links->course_tool_editor = $coursetooleditor->out(false);

            echo get_string('mcodelti_launch_error_unsigned_help', 'mcodelti', $links);
        }

        if (!empty($mcodelti) && has_capability('mod/mcodelti:requesttooladd', $contextcourse)) {
            $adminrequesturl = new moodle_url('/mod/mcodelti/request_tool.php', array('instanceid' => $mcodelti->id, 'sesskey' => sesskey()));
            $links->admin_request_url = $adminrequesturl->out(false);

            echo get_string('mcodelti_launch_error_tool_request', 'mcodelti', $links);
        }
    }

    echo $OUTPUT->footer();
} else if (!empty($msg)) {

    p($msg);

    echo $OUTPUT->footer();

} else {
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    $url = $courseurl->out();

    // Avoid frame-in-frame action.
    if ($launchcontainer == mcodelti_LAUNCH_CONTAINER_EMBED || $launchcontainer == mcodelti_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
        // Output a page containing some script to break out of frames and redirect them.

        echo '<html><body>';

        $script = "
            <script type=\"text/javascript\">
            //<![CDATA[
                if(window != top){
                    top.location.href = '{$url}';
                }
            //]]
            </script>
        ";

        $clickhere = get_string('return_to_course', 'mcodelti', (object)array('link' => $url));

        $noscript = "
            <noscript>
                {$clickhere}
            </noscript>
        ";

        echo $script;
        echo $noscript;

        echo '</body></html>';
    } else {
        // If no error, take them back to the course.
        redirect($url);
    }
}
