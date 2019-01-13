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
 * This page allows the configuration of external tools that meet the mcodelti specification.
 *
 * @package    mod_mcodelti
 * @copyright  2015 Ryan Wyllie <ryan@moodle.com>
 * @author     Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/mcodelti/lib.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

$cartridgeurl = optional_param('cartridgeurl', '', PARAM_URL);

// No guest autologin.
require_login(0, false);
admin_externalpage_setup('mcodeltitoolconfigure');

if ($cartridgeurl) {
    $type = new stdClass();
    $data = new stdClass();
    $type->state = mcodelti_TOOL_STATE_CONFIGURED;
    $data->mcodelti_coursevisible = 1;
    mcodelti_load_type_from_cartridge($cartridgeurl, $data);
    mcodelti_add_type($type, $data);
}

$pageurl = new moodle_url('/mod/mcodelti/toolconfigure.php');
$PAGE->set_url($pageurl);
$PAGE->set_title("{$SITE->shortname}: " . get_string('toolregistration', 'mod_mcodelti'));
$PAGE->requires->string_for_js('success', 'moodle');
$PAGE->requires->string_for_js('error', 'moodle');
$PAGE->requires->string_for_js('successfullycreatedtooltype', 'mod_mcodelti');
$PAGE->requires->string_for_js('failedtocreatetooltype', 'mod_mcodelti');
$output = $PAGE->get_renderer('mod_mcodelti');

echo $output->header();

$page = new \mod_mcodelti\output\tool_configure_page();
echo $output->render($page);

echo $output->footer();
