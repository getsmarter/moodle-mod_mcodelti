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
 * Submits a request to administrators to add a tool configuration for the requested site.
 *
 * @package mod_mcodelti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/mcodelti/lib.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

$instanceid = required_param('instanceid', PARAM_INT);

$mcodelti = $DB->get_record('mcodelti', array('id' => $instanceid));
$course = $DB->get_record('course', array('id' => $mcodelti->course));
$cm = get_coursemodule_from_instance('mcodelti', $mcodelti->id, $mcodelti->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course);

require_sesskey();

require_capability('mod/mcodelti:requesttooladd', context_course::instance($mcodelti->course));

$baseurl = mcodelti_get_domain_from_url($mcodelti->toolurl);

$url = new moodle_url('/mod/mcodelti/request_tool.php', array('instanceid' => $instanceid));
$PAGE->set_url($url);

$pagetitle = strip_tags($course->shortname);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($mcodelti->name, true, array('context' => $context)));

// Add a tool type if one does not exist already.
if (!mcodelti_get_tool_by_url_match($mcodelti->toolurl, $mcodelti->course, mcodelti_TOOL_STATE_ANY)) {
    // There are no tools (active, pending, or rejected) for the launch URL. Create a new pending tool.
    $tooltype = new stdClass();
    $toolconfig = new stdClass();

    $toolconfig->mcodelti_toolurl = mcodelti_get_domain_from_url($mcodelti->toolurl);
    $toolconfig->mcodelti_typename = $toolconfig->mcodelti_toolurl;

    mcodelti_add_type($tooltype, $toolconfig);

    echo get_string('mcodelti_tool_request_added', 'mcodelti');
} else {
    echo get_string('mcodelti_tool_request_existing', 'mcodelti');
}

echo $OUTPUT->footer();
