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
 * mcodelti web service endpoints
 *
 * @package mod_mcodelti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . "/../../config.php");
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');
require_once($CFG->dirroot.'/mod/mcodelti/servicelib.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use mod_mcodelti\service_exception_handler;
use moodle\mod\mcodelti as mcodelti;

$rawbody = file_get_contents("php://input");

$logrequests  = mcodelti_should_log_request($rawbody);
$errorhandler = new service_exception_handler($logrequests);

// Register our own error handler so we can always send valid XML response.
set_exception_handler(array($errorhandler, 'handle'));

if ($logrequests) {
    mcodelti_log_request($rawbody);
}

foreach (mcodelti\OAuthUtil::get_headers() as $name => $value) {
    if ($name === 'Authorization') {
        // TODO: Switch to core oauthlib once implemented - MDL-30149.
        $oauthparams = mcodelti\OAuthUtil::split_header($value);

        $consumerkey = $oauthparams['oauth_consumer_key'];
        break;
    }
}

if (empty($consumerkey)) {
    throw new Exception('Consumer key is missing.');
}

$sharedsecret = mcodelti_verify_message($consumerkey, mcodelti_get_shared_secrets_by_key($consumerkey), $rawbody);

if ($sharedsecret === false) {
    throw new Exception('Message signature not valid');
}

// TODO MDL-46023 Replace this code with a call to the new library.
$origentity = libxml_disable_entity_loader(true);
$xml = simplexml_load_string($rawbody);
if (!$xml) {
    libxml_disable_entity_loader($origentity);
    throw new Exception('Invalid XML content');
}
libxml_disable_entity_loader($origentity);

$body = $xml->imsx_POXBody;
foreach ($body->children() as $child) {
    $messagetype = $child->getName();
}

// We know more about the message, update error handler to send better errors.
$errorhandler->set_message_id(mcodelti_parse_message_id($xml));
$errorhandler->set_message_type($messagetype);

switch ($messagetype) {
    case 'replaceResultRequest':
        $parsed = mcodelti_parse_grade_replace_message($xml);

        $mcodeltiinstance = $DB->get_record('mcodelti', array('id' => $parsed->instanceid));

        if (!mcodelti_accepts_grades($mcodeltiinstance)) {
            throw new Exception('Tool does not accept grades');
        }

        mcodelti_verify_sourcedid($mcodeltiinstance, $parsed);
        mcodelti_set_session_user($parsed->userid);

        $gradestatus = mcodelti_update_grade($mcodeltiinstance, $parsed->userid, $parsed->launchid, $parsed->gradeval);

        if (!$gradestatus) {
            throw new Exception('Grade replace response');
        }

        $responsexml = mcodelti_get_response_xml(
                'success',
                'Grade replace response',
                $parsed->messageid,
                'replaceResultResponse'
        );

        echo $responsexml->asXML();

        break;

    case 'readResultRequest':
        $parsed = mcodelti_parse_grade_read_message($xml);

        $mcodeltiinstance = $DB->get_record('mcodelti', array('id' => $parsed->instanceid));

        if (!mcodelti_accepts_grades($mcodeltiinstance)) {
            throw new Exception('Tool does not accept grades');
        }

        // Getting the grade requires the context is set.
        $context = context_course::instance($mcodeltiinstance->course);
        $PAGE->set_context($context);

        mcodelti_verify_sourcedid($mcodeltiinstance, $parsed);

        $grade = mcodelti_read_grade($mcodeltiinstance, $parsed->userid);

        $responsexml = mcodelti_get_response_xml(
                'success',  // Empty grade is also 'success'.
                'Result read',
                $parsed->messageid,
                'readResultResponse'
        );

        $node = $responsexml->imsx_POXBody->readResultResponse;
        $node = $node->addChild('result')->addChild('resultScore');
        $node->addChild('language', 'en');
        $node->addChild('textString', isset($grade) ? $grade : '');

        echo $responsexml->asXML();

        break;

    case 'deleteResultRequest':
        $parsed = mcodelti_parse_grade_delete_message($xml);

        $mcodeltiinstance = $DB->get_record('mcodelti', array('id' => $parsed->instanceid));

        if (!mcodelti_accepts_grades($mcodeltiinstance)) {
            throw new Exception('Tool does not accept grades');
        }

        mcodelti_verify_sourcedid($mcodeltiinstance, $parsed);
        mcodelti_set_session_user($parsed->userid);

        $gradestatus = mcodelti_delete_grade($mcodeltiinstance, $parsed->userid);

        if (!$gradestatus) {
            throw new Exception('Grade delete request');
        }

        $responsexml = mcodelti_get_response_xml(
                'success',
                'Grade delete request',
                $parsed->messageid,
                'deleteResultResponse'
        );

        echo $responsexml->asXML();

        break;

    default:
        // Fire an event if we get a web service request which we don't support directly.
        // This will allow others to extend the mcodelti services, which I expect to be a common
        // use case, at least until the spec matures.
        $data = new stdClass();
        $data->body = $rawbody;
        $data->xml = $xml;
        $data->messageid = mcodelti_parse_message_id($xml);
        $data->messagetype = $messagetype;
        $data->consumerkey = $consumerkey;
        $data->sharedsecret = $sharedsecret;
        $eventdata = array();
        $eventdata['other'] = array();
        $eventdata['other']['messageid'] = $data->messageid;
        $eventdata['other']['messagetype'] = $messagetype;
        $eventdata['other']['consumerkey'] = $consumerkey;

        // Before firing the event, allow subplugins a chance to handle.
        if (mcodelti_extend_mcodelti_services($data)) {
            break;
        }

        // If an event handler handles the web service, it should set this global to true
        // So this code knows whether to send an "operation not supported" or not.
        global $mcodeltiwebservicehandled;
        $mcodeltiwebservicehandled = false;

        try {
            $event = \mod_mcodelti\event\unknown_service_api_called::create($eventdata);
            $event->set_message_data($data);
            $event->trigger();
        } catch (Exception $e) {
            $mcodeltiwebservicehandled = false;
        }

        if (!$mcodeltiwebservicehandled) {
            $responsexml = mcodelti_get_response_xml(
                'unsupported',
                'unsupported',
                 mcodelti_parse_message_id($xml),
                 $messagetype
            );

            echo $responsexml->asXML();
        }

        break;
}
