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
 * Utility code for mcodelti service handling.
 *
 * @package mod_mcodelti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/mcodelti/OAuthBody.php');
require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\mod\mcodelti as mcodelti;

define('mcodelti_ITEM_TYPE', 'mod');
define('mcodelti_ITEM_MODULE', 'mcodelti');
define('mcodelti_SOURCE', 'mod/mcodelti');

function mcodelti_get_response_xml($codemajor, $description, $messageref, $messagetype) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><imsx_POXEnvelopeResponse />');
    $xml->addAttribute('xmlns', 'http://www.imsglobal.org/services/mcodeltiv1p1/xsd/imsoms_v1p0');

    $headerinfo = $xml->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');

    $headerinfo->addChild('imsx_version', 'V1.0');
    $headerinfo->addChild('imsx_messageIdentifier', (string)mt_rand());

    $statusinfo = $headerinfo->addChild('imsx_statusInfo');
    $statusinfo->addchild('imsx_codeMajor', $codemajor);
    $statusinfo->addChild('imsx_severity', 'status');
    $statusinfo->addChild('imsx_description', $description);
    $statusinfo->addChild('imsx_messageRefIdentifier', $messageref);
    $incomingtype = str_replace('Response', 'Request', $messagetype);
    $statusinfo->addChild('imsx_operationRefIdentifier', $incomingtype);

    $xml->addChild('imsx_POXBody')->addChild($messagetype);

    return $xml;
}

function mcodelti_parse_message_id($xml) {
    if (empty($xml->imsx_POXHeader)) {
        return '';
    }

    $node = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
    $messageid = (string)$node;

    return $messageid;
}

function mcodelti_parse_grade_replace_message($xml) {
    $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);

    $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;

    $score = (string) $node;
    if ( ! is_numeric($score) ) {
        throw new Exception('Score must be numeric');
    }
    $grade = floatval($score);
    if ( $grade < 0.0 || $grade > 1.0 ) {
        throw new Exception('Score not between 0.0 and 1.0');
    }

    $parsed = new stdClass();
    $parsed->gradeval = $grade;

    $parsed->instanceid = $resultjson->data->instanceid;
    $parsed->userid = $resultjson->data->userid;
    $parsed->launchid = $resultjson->data->launchid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->sourcedidhash = $resultjson->hash;

    $parsed->messageid = mcodelti_parse_message_id($xml);

    return $parsed;
}

function mcodelti_parse_grade_read_message($xml) {
    $node = $xml->imsx_POXBody->readResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);

    $parsed = new stdClass();
    $parsed->instanceid = $resultjson->data->instanceid;
    $parsed->userid = $resultjson->data->userid;
    $parsed->launchid = $resultjson->data->launchid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->sourcedidhash = $resultjson->hash;

    $parsed->messageid = mcodelti_parse_message_id($xml);

    return $parsed;
}

function mcodelti_parse_grade_delete_message($xml) {
    $node = $xml->imsx_POXBody->deleteResultRequest->resultRecord->sourcedGUID->sourcedId;
    $resultjson = json_decode((string)$node);

    $parsed = new stdClass();
    $parsed->instanceid = $resultjson->data->instanceid;
    $parsed->userid = $resultjson->data->userid;
    $parsed->launchid = $resultjson->data->launchid;
    $parsed->typeid = $resultjson->data->typeid;
    $parsed->sourcedidhash = $resultjson->hash;

    $parsed->messageid = mcodelti_parse_message_id($xml);

    return $parsed;
}

function mcodelti_accepts_grades($mcodeltiinstance) {
    global $DB;

    $acceptsgrades = true;
    $mcodeltitype = $DB->get_record('mcodelti_types', array('id' => $mcodeltiinstance->typeid));

    if (empty($mcodeltitype->toolproxyid)) {
        $typeconfig = mcodelti_get_config($mcodeltiinstance);

        $typeacceptgrades = isset($typeconfig['acceptgrades']) ? $typeconfig['acceptgrades'] : mcodelti_SETTING_DELEGATE;

        if (!($typeacceptgrades == mcodelti_SETTING_ALWAYS ||
            ($typeacceptgrades == mcodelti_SETTING_DELEGATE && $mcodeltiinstance->instructorchoiceacceptgrades == mcodelti_SETTING_ALWAYS))) {
            $acceptsgrades = false;
        }
    } else {
        $enabledcapabilities = explode("\n", $mcodeltitype->enabledcapability);
        $acceptsgrades = in_array('Result.autocreate', $enabledcapabilities);
    }

    return $acceptsgrades;
}

/**
 * Set the passed user ID to the session user.
 *
 * @param int $userid
 */
function mcodelti_set_session_user($userid) {
    global $DB;

    if ($user = $DB->get_record('user', array('id' => $userid))) {
        \core\session\manager::set_user($user);
    }
}

function mcodelti_update_grade($mcodeltiinstance, $userid, $launchid, $gradeval) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $params = array();
    $params['itemname'] = $mcodeltiinstance->name;

    $gradeval = $gradeval * floatval($mcodeltiinstance->grade);

    $grade = new stdClass();
    $grade->userid   = $userid;
    $grade->rawgrade = $gradeval;

    $status = grade_update(mcodelti_SOURCE, $mcodeltiinstance->course, mcodelti_ITEM_TYPE, mcodelti_ITEM_MODULE, $mcodeltiinstance->id, 0, $grade, $params);

    $record = $DB->get_record('mcodelti_submission', array('mcodeltiid' => $mcodeltiinstance->id, 'userid' => $userid,
        'launchid' => $launchid), 'id');
    if ($record) {
        $id = $record->id;
    } else {
        $id = null;
    }

    if (!empty($id)) {
        $DB->update_record('mcodelti_submission', array(
            'id' => $id,
            'dateupdated' => time(),
            'gradepercent' => $gradeval,
            'state' => 2
        ));
    } else {
        $DB->insert_record('mcodelti_submission', array(
            'mcodeltiid' => $mcodeltiinstance->id,
            'userid' => $userid,
            'datesubmitted' => time(),
            'dateupdated' => time(),
            'gradepercent' => $gradeval,
            'originalgrade' => $gradeval,
            'launchid' => $launchid,
            'state' => 1
        ));
    }

    return $status == GRADE_UPDATE_OK;
}

function mcodelti_read_grade($mcodeltiinstance, $userid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = grade_get_grades($mcodeltiinstance->course, mcodelti_ITEM_TYPE, mcodelti_ITEM_MODULE, $mcodeltiinstance->id, $userid);

    $mcodeltigrade = floatval($mcodeltiinstance->grade);

    if (!empty($mcodeltigrade) && isset($grades) && isset($grades->items[0]) && is_array($grades->items[0]->grades)) {
        foreach ($grades->items[0]->grades as $agrade) {
            $grade = $agrade->grade;
            if (isset($grade)) {
                return $grade / $mcodeltigrade;
            }
        }
    }
}

function mcodelti_delete_grade($mcodeltiinstance, $userid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $grade = new stdClass();
    $grade->userid   = $userid;
    $grade->rawgrade = null;

    $status = grade_update(mcodelti_SOURCE, $mcodeltiinstance->course, mcodelti_ITEM_TYPE, mcodelti_ITEM_MODULE, $mcodeltiinstance->id, 0, $grade);

    return $status == GRADE_UPDATE_OK;
}

function mcodelti_verify_message($key, $sharedsecrets, $body, $headers = null) {
    foreach ($sharedsecrets as $secret) {
        $signaturefailed = false;

        try {
            // TODO: Switch to core oauthlib once implemented - MDL-30149.
            mcodelti\handle_oauth_body_post($key, $secret, $body, $headers);
        } catch (Exception $e) {
            debugging('mcodelti message verification failed: '.$e->getMessage());
            $signaturefailed = true;
        }

        if (!$signaturefailed) {
            return $secret; // Return the secret used to sign the message).
        }
    }

    return false;
}

/**
 * Validate source ID from external request
 *
 * @param object $mcodeltiinstance
 * @param object $parsed
 * @throws Exception
 */
function mcodelti_verify_sourcedid($mcodeltiinstance, $parsed) {
    $sourceid = mcodelti_build_sourcedid($parsed->instanceid, $parsed->userid,
        $mcodeltiinstance->servicesalt, $parsed->typeid, $parsed->launchid);

    if ($sourceid->hash != $parsed->sourcedidhash) {
        throw new Exception('SourcedId hash not valid');
    }
}

/**
 * Extend the mcodelti services through the mcodeltisource plugins
 *
 * @param stdClass $data mcodelti request data
 * @return bool
 * @throws coding_exception
 */
function mcodelti_extend_mcodelti_services($data) {
    $plugins = get_plugin_list_with_function('mcodeltisource', $data->messagetype);
    if (!empty($plugins)) {
        // There can only be one.
        if (count($plugins) > 1) {
            throw new coding_exception('More than one mcodeltisource plugin handler found');
        }
        $data->xml = new SimpleXMLElement($data->body);
        $callback = current($plugins);
        call_user_func($callback, $data);

        return true;
    }
    return false;
}
