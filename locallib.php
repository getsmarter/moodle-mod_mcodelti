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
 * This file contains the library of functions and constants for the mcodelti module
 *
 * @package mod_mcodelti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @copyright  2015 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use moodle\mod\mcodelti as mcodelti;

require_once($CFG->dirroot.'/mod/mcodelti/OAuth.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/mcodelti/TrivialStore.php');

define('MCODELTI_URL_DOMAIN_REGEX', '/(?:https?:\/\/)?(?:www\.)?([^\/]+)(?:\/|$)/i');

define('MCODELTI_LAUNCH_CONTAINER_DEFAULT', 1);
define('MCODELTI_LAUNCH_CONTAINER_EMBED', 2);
define('MCODELTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS', 3);
define('MCODELTI_LAUNCH_CONTAINER_WINDOW', 4);
define('MCODELTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW', 5);

define('MCODELTI_TOOL_STATE_ANY', 0);
define('MCODELTI_TOOL_STATE_CONFIGURED', 1);
define('MCODELTI_TOOL_STATE_PENDING', 2);
define('MCODELTI_TOOL_STATE_REJECTED', 3);
define('MCODELTI_TOOL_PROXY_TAB', 4);

define('MCODELTI_TOOL_PROXY_STATE_CONFIGURED', 1);
define('MCODELTI_TOOL_PROXY_STATE_PENDING', 2);
define('MCODELTI_TOOL_PROXY_STATE_ACCEPTED', 3);
define('MCODELTI_TOOL_PROXY_STATE_REJECTED', 4);

define('MCODELTI_SETTING_NEVER', 0);
define('MCODELTI_SETTING_ALWAYS', 1);
define('MCODELTI_SETTING_DELEGATE', 2);

define('MCODELTI_COURSEVISIBLE_NO', 0);
define('MCODELTI_COURSEVISIBLE_PRECONFIGURED', 1);
define('MCODELTI_COURSEVISIBLE_ACTIVITYCHOOSER', 2);

define('MCODELTI_VERSION_1', 'mcodelti-1p0');
define('MCODELTI_VERSION_2', 'mcodelti-2p0');


// Assignment submission statuses.
define('MCODELTI_SUBMISSION_STATUS_NEW', 'new');
define('MCODE_SUBMISSION_STATUS_REOPENED', 'reopened');
define('MCODE_SUBMISSION_STATUS_DRAFT', 'draft');
define('MCODE_SUBMISSION_STATUS_SUBMITTED', 'submitted');

// Search filters for grading page.
define('MCODE_FILTER_SUBMITTED', 'submitted');
define('MCODE_FILTER_NOT_SUBMITTED', 'notsubmitted');
define('MCODE_FILTER_SINGLE_USER', 'singleuser');
define('MCODE_FILTER_REQUIRE_GRADING', 'require_grading');
define('MCODE_FILTER_GRANTED_EXTENSION', 'granted_extension');

/**
 * Return the launch data required for opening the external tool.
 *
 * @param  stdClass $instance the external tool activity settings
 * @return array the endpoint URL and parameters (including the signature)
 * @since  Moodle 3.0
 */
function mcodelti_get_launch_data($instance) {
    global $PAGE, $CFG;

    if (empty($instance->typeid)) {
        $tool = mcodelti_get_tool_by_url_match($instance->toolurl, $instance->course);
        if ($tool) {
            $typeid = $tool->id;
        } else {
            $typeid = null;
        }
    } else {
        $typeid = $instance->typeid;
        $tool = mcodelti_get_type($typeid);
    }


    if ($typeid) {
        $typeconfig = mcodelti_get_type_config($typeid);
    } else {
        // There is no admin configuration for this tool. Use configuration in the mcodelti instance record plus some defaults.
        $typeconfig = (array)$instance;

        $typeconfig['sendname'] = $instance->instructorchoicesendname;
        $typeconfig['sendemailaddr'] = $instance->instructorchoicesendemailaddr;
        $typeconfig['customparameters'] = $instance->instructorcustomparameters;
        $typeconfig['acceptgrades'] = $instance->instructorchoiceacceptgrades;
        $typeconfig['allowroster'] = $instance->instructorchoiceallowroster;
        $typeconfig['forcessl'] = '0';
    }


    // Default the organizationid if not specified.
    if (empty($typeconfig['organizationid'])) {
        $urlparts = parse_url($CFG->wwwroot);

        $typeconfig['organizationid'] = $urlparts['host'];
    }

    if (isset($tool->toolproxyid)) {
        $toolproxy = mcodelti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $toolproxy = null;
        if (!empty($instance->resourcekey)) {
            $key = $instance->resourcekey;
        } else if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        } else {
            $key = '';
        }
        if (!empty($instance->password)) {
            $secret = $instance->password;
        } else if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        } else {
            $secret = '';
        }
    }

    $endpoint = !empty($instance->toolurl) ? $instance->toolurl : $typeconfig['toolurl'];
    $endpoint = trim($endpoint);

    // If the current request is using SSL and a secure tool URL is specified, use it.
    if (mcodelti_request_is_using_ssl() && !empty($instance->securetoolurl)) {
        $endpoint = trim($instance->securetoolurl);
    }

    // If SSL is forced, use the secure tool url if specified. Otherwise, make sure https is on the normal launch URL.
    if (isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) {
        if (!empty($instance->securetoolurl)) {
            $endpoint = trim($instance->securetoolurl);
        }

        $endpoint = mcodelti_ensure_url_is_https($endpoint);
    } else {
        if (!strstr($endpoint, '://')) {
            $endpoint = 'http://' . $endpoint;
        }
    }

    $orgid = $typeconfig['organizationid'];

    $course = $PAGE->course;
    $ismcodelti2 = isset($tool->toolproxyid);
    $allparams = mcodelti_build_request($instance, $typeconfig, $course, $typeid, $ismcodelti2);
    if ($ismcodelti2) {
   
        $requestparams = mcodelti_build_request_mcodelti2($tool, $allparams);
    } else {
        $requestparams = $allparams;
    }

    $requestparams = array_merge($requestparams, mcodelti_build_standard_request($instance, $orgid, $ismcodelti2));


    $customstr = '';
    if (isset($typeconfig['customparameters'])) {
        $customstr = $typeconfig['customparameters'];
    }
    $requestparams = array_merge($requestparams, mcodelti_build_custom_parameters($toolproxy, $tool, $instance, $allparams, $customstr,
        $instance->instructorcustomparameters, $ismcodelti2));



    $launchcontainer = mcodelti_get_launch_container($instance, $typeconfig);
    $returnurlparams = array('course' => $course->id,
                             'launch_container' => $launchcontainer,
                             'instanceid' => $instance->id,
                             'sesskey' => sesskey());


    // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
    $url = new \moodle_url('/mod/mcodelti/return.php', $returnurlparams);
    $returnurl = $url->out(false);

    if (isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) {
        $returnurl = mcodelti_ensure_url_is_https($returnurl);
    }

    $target = '';
    switch($launchcontainer) {
        case MCODELTI_LAUNCH_CONTAINER_EMBED:
        case MCODELTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS:
            $target = 'iframe';
            break;
        case MCODELTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW:
            $target = 'frame';
            break;
        case MCODELTI_LAUNCH_CONTAINER_WINDOW:
            $target = 'window';
            break;
    }
    if (!empty($target)) {
        $requestparams['launch_presentation_document_target'] = $target;
    }

    $requestparams['launch_presentation_return_url'] = $returnurl;

    if (!empty($key) && !empty($secret)) {
        $parms = mcodelti_sign_parameters($requestparams, $endpoint, "POST", $key, $secret);
                



        $endpointurl = new \moodle_url($endpoint);
        $endpointparams = $endpointurl->params();

        // Strip querystring params in endpoint url from $parms to avoid duplication.
        if (!empty($endpointparams) && !empty($parms)) {
            foreach (array_keys($endpointparams) as $paramname) {
                if (isset($parms[$paramname])) {
                    unset($parms[$paramname]);
                }
            }
        }

    } else {
        // If no key and secret, do the launch unsigned.
        $returnurlparams['unsigned'] = '1';
        $parms = $requestparams;
    }

    return array($endpoint, $parms);
}

/**
 * Launch an external tool activity.
 *
 * @param  stdClass $instance the external tool activity settings
 * @return string The HTML code containing the javascript code for the launch
 */
function mcodelti_launch_tool($instance) {

    list($endpoint, $parms) = mcodelti_get_launch_data($instance);
    $debuglaunch = ( $instance->debuglaunch == 1 );

    $content = mcodelti_post_launch_html($parms, $endpoint, $debuglaunch);

    echo $content;
}

/**
 * Prepares an mcodelti registration request message
 *
 * $param object $instance       Tool Proxy instance object
 */
function mcodelti_register($toolproxy) {
    $endpoint = $toolproxy->regurl;

    // Change the status to pending.
    $toolproxy->state = MCODELTI_TOOL_PROXY_STATE_PENDING;
    mcodelti_update_tool_proxy($toolproxy);

    $requestparams = mcodelti_build_registration_request($toolproxy);

    $content = mcodelti_post_launch_html($requestparams, $endpoint, false);

    echo $content;
}


/**
 * Gets the parameters for the regirstration request
 *
 * @param object $toolproxy Tool Proxy instance object
 * @return array Registration request parameters
 */
function mcodelti_build_registration_request($toolproxy) {
    $key = $toolproxy->guid;
    $secret = $toolproxy->secret;

    $requestparams = array();
    $requestparams['mcodelti_message_type'] = 'ToolProxyRegistrationRequest';
    $requestparams['mcodelti_version'] = 'mcodelti-2p0';
    $requestparams['reg_key'] = $key;
    $requestparams['reg_password'] = $secret;
    $requestparams['reg_url'] = $toolproxy->regurl;

    // Add the profile URL.
    $profileservice = mcodelti_get_service_by_name('profile');
    $profileservice->set_tool_proxy($toolproxy);
    $requestparams['tc_profile_url'] = $profileservice->parse_value('$ToolConsumerProfile.url');

    // Add the return URL.
    $returnurlparams = array('id' => $toolproxy->id, 'sesskey' => sesskey());
    $url = new \moodle_url('/mod/mcodelti/externalregistrationreturn.php', $returnurlparams);
    $returnurl = $url->out(false);

    $requestparams['launch_presentation_return_url'] = $returnurl;

    return $requestparams;
}

/**
 * Build source ID
 *
 * @param int $instanceid
 * @param int $userid
 * @param string $servicesalt
 * @param null|int $typeid
 * @param null|int $launchid
 * @return stdClass
 */
function mcodelti_build_sourcedid($instanceid, $userid, $servicesalt, $typeid = null, $launchid = null) {
    $data = new \stdClass();

    $data->instanceid = $instanceid;
    $data->userid = $userid;
    $data->typeid = $typeid;
    if (!empty($launchid)) {
        $data->launchid = $launchid;
    } else {
        $data->launchid = mt_rand();
    }

    $json = json_encode($data);

    $hash = hash('sha256', $json . $servicesalt, false);

    $container = new \stdClass();
    $container->data = $data;
    $container->hash = $hash;

    return $container;
}

/**
 * This function builds the request that must be sent to the tool producer
 *
 * @param object    $instance       Basic mcodelti instance object
 * @param array     $typeconfig     Basic mcodelti tool configuration
 * @param object    $course         Course object
 * @param int|null  $typeid         Basic mcodelti tool ID
 * @param boolean   $ismcodelti2         True if an mcodelti 2 tool is being launched
 *
 * @return array                    Request details
 */
function mcodelti_build_request($instance, $typeconfig, $course, $typeid = null, $ismcodelti2 = false) {
    global $USER, $CFG;

    if (empty($instance->cmid)) {
        $instance->cmid = 0;
    }

    $role = mcodelti_get_ims_role($USER, $instance->cmid, $instance->course, $ismcodelti2);

    $requestparams = array(
        'user_id' => $USER->id,
        'lis_person_sourcedid' => $USER->idnumber,
        'roles' => $role,
        'context_id' => $course->id,
        'context_label' => trim(html_to_text($course->shortname, 0)),
        'context_title' => trim(html_to_text($course->fullname, 0)),
    );
    if (!empty($instance->name)) {
        $requestparams['resource_link_title'] = trim(html_to_text($instance->name, 0));
    }
    if (!empty($instance->cmid)) {
        $intro = format_module_intro('mcodelti', $instance, $instance->cmid);
        $intro = trim(html_to_text($intro, 0, false));

        // This may look weird, but this is required for new lines
        // so we generate the same OAuth signature as the tool provider.
        $intro = str_replace("\n", "\r\n", $intro);
        $requestparams['resource_link_description'] = $intro;
    }
    if (!empty($instance->id)) {
        $requestparams['resource_link_id'] = $instance->id;
    }
    if (!empty($instance->resource_link_id)) {
        $requestparams['resource_link_id'] = $instance->resource_link_id;
    }
    if ($course->format == 'site') {
        $requestparams['context_type'] = 'Group';
    } else {
        $requestparams['context_type'] = 'CourseSection';
        $requestparams['lis_course_section_sourcedid'] = $course->idnumber;
    }

    if (!empty($instance->id) && !empty($instance->servicesalt) && ($ismcodelti2 ||
            $typeconfig['acceptgrades'] == MCODELTI_SETTING_ALWAYS ||
            ($typeconfig['acceptgrades'] == MCODELTI_SETTING_DELEGATE && $instance->instructorchoiceacceptgrades == MCODELTI_SETTING_ALWAYS))
    ) {
        $placementsecret = $instance->servicesalt;
        $sourcedid = json_encode(mcodelti_build_sourcedid($instance->id, $USER->id, $placementsecret, $typeid));
        $requestparams['lis_result_sourcedid'] = $sourcedid;

        // Add outcome service URL.
        $serviceurl = new \moodle_url('/mod/mcodelti/service.php');
        $serviceurl = $serviceurl->out();

        $forcessl = false;
        if (!empty($CFG->mod_mcodelti_forcessl)) {
            $forcessl = true;
        }

        if ((isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) or $forcessl) {
            $serviceurl = mcodelti_ensure_url_is_https($serviceurl);
        }

        $requestparams['lis_outcome_service_url'] = $serviceurl;
    }

    // Send user's name and email data if appropriate.
    if ($ismcodelti2 || $typeconfig['sendname'] == MCODELTI_SETTING_ALWAYS ||
        ($typeconfig['sendname'] == MCODELTI_SETTING_DELEGATE && isset($instance->instructorchoicesendname)
            && $instance->instructorchoicesendname == MCODELTI_SETTING_ALWAYS)
    ) {
        $requestparams['lis_person_name_given'] = $USER->firstname;
        $requestparams['lis_person_name_family'] = $USER->lastname;
        $requestparams['lis_person_name_full'] = $USER->firstname . ' ' . $USER->lastname;
        $requestparams['ext_user_username'] = $USER->username;
    }

    if ($ismcodelti2 || $typeconfig['sendemailaddr'] == MCODELTI_SETTING_ALWAYS ||
        ($typeconfig['sendemailaddr'] == MCODELTI_SETTING_DELEGATE && isset($instance->instructorchoicesendemailaddr)
            && $instance->instructorchoicesendemailaddr == MCODELTI_SETTING_ALWAYS)
    ) {
        $requestparams['lis_person_contact_email_primary'] = $USER->email;
    }

    return $requestparams;
}

/**
 * This function builds the request that must be sent to an mcodelti 2 tool provider
 *
 * @param object    $tool           Basic mcodelti tool object
 * @param array     $params         Custom launch parameters
 *
 * @return array                    Request details
 */
function mcodelti_build_request_mcodelti2($tool, $params) {

    $requestparams = array();

    $capabilities = mcodelti_get_capabilities();
    $enabledcapabilities = explode("\n", $tool->enabledcapability);
    foreach ($enabledcapabilities as $capability) {
        if (array_key_exists($capability, $capabilities)) {
            $val = $capabilities[$capability];
            if ($val && (substr($val, 0, 1) != '$')) {
                if (isset($params[$val])) {
                    $requestparams[$capabilities[$capability]] = $params[$capabilities[$capability]];
                }
            }
        }
    }

    return $requestparams;

}

/**
 * This function builds the standard parameters for an mcodelti 1 or 2 request that must be sent to the tool producer
 *
 * @param stdClass  $instance       Basic mcodelti instance object
 * @param string    $orgid          Organisation ID
 * @param boolean   $ismcodelti2         True if an mcodelti 2 tool is being launched
 * @param string    $messagetype    The request message type. Defaults to basic-mcodelti-launch-request if empty.
 *
 * @return array                    Request details
 */
function mcodelti_build_standard_request($instance, $orgid, $ismcodelti2, $messagetype = 'basic-mcodelti-launch-request') {
    global $CFG;

    $requestparams = array();

    if ($instance) {
        $requestparams['resource_link_id'] = $instance->id;
        if (property_exists($instance, 'resource_link_id') and !empty($instance->resource_link_id)) {
            $requestparams['resource_link_id'] = $instance->resource_link_id;
        }
    }

    $requestparams['launch_presentation_locale'] = current_language();

    // Make sure we let the tool know what LMS they are being called from.
    $requestparams['ext_lms'] = 'moodle-2';
    $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
    $requestparams['tool_consumer_info_version'] = strval($CFG->version);

    // Add oauth_callback to be compliant with the 1.0A spec.
    $requestparams['oauth_callback'] = 'about:blank';

    if (!$ismcodelti2) {
        $requestparams['mcodelti_version'] = 'mcodelti-1p0';
    } else {
        $requestparams['mcodelti_version'] = 'mcodelti-2p0';
    }
    $requestparams['mcodelti_message_type'] = $messagetype;

    if ($orgid) {
        $requestparams["tool_consumer_instance_guid"] = $orgid;
    }
    if (!empty($CFG->mod_mcodelti_institution_name)) {
        $requestparams['tool_consumer_instance_name'] = trim(html_to_text($CFG->mod_mcodelti_institution_name, 0));
    } else {
        $requestparams['tool_consumer_instance_name'] = get_site()->shortname;
    }
    $requestparams['tool_consumer_instance_description'] = trim(html_to_text(get_site()->fullname, 0));

    return $requestparams;
}

/**
 * This function builds the custom parameters
 *
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param object    $instance       Tool placement instance object
 * @param array     $params         mcodelti launch parameters
 * @param string    $customstr      Custom parameters defined for tool
 * @param string    $instructorcustomstr      Custom parameters defined for this placement
 * @param boolean   $ismcodelti2         True if an mcodelti 2 tool is being launched
 *
 * @return array                    Custom parameters
 */
function mcodelti_build_custom_parameters($toolproxy, $tool, $instance, $params, $customstr, $instructorcustomstr, $ismcodelti2) {

    // Concatenate the custom parameters from the administrator and the instructor
    // Instructor parameters are only taken into consideration if the administrator
    // has given permission.
    $custom = array();
    if ($customstr) {
        $custom = mcodelti_split_custom_parameters($toolproxy, $tool, $params, $customstr, $ismcodelti2);
    }
    if (!isset($typeconfig['allowinstructorcustom']) || $typeconfig['allowinstructorcustom'] != MCODELTI_SETTING_NEVER) {
        if ($instructorcustomstr) {
            $custom = array_merge(mcodelti_split_custom_parameters($toolproxy, $tool, $params,
                $instructorcustomstr, $ismcodelti2), $custom);
        }
    }
    if ($ismcodelti2) {
        $custom = array_merge(mcodelti_split_custom_parameters($toolproxy, $tool, $params,
            $tool->parameter, true), $custom);
        $settings = mcodelti_get_tool_settings($tool->toolproxyid);
        $custom = array_merge($custom, mcodelti_get_custom_parameters($toolproxy, $tool, $params, $settings));
        if (!empty($instance->course)) {
            $settings = mcodelti_get_tool_settings($tool->toolproxyid, $instance->course);
            $custom = array_merge($custom, mcodelti_get_custom_parameters($toolproxy, $tool, $params, $settings));
            if (!empty($instance->id)) {
                $settings = mcodelti_get_tool_settings($tool->toolproxyid, $instance->course, $instance->id);
                $custom = array_merge($custom, mcodelti_get_custom_parameters($toolproxy, $tool, $params, $settings));
            }
        }
    }

    return $custom;
}

/**
 * Builds a standard mcodelti Content-Item selection request.
 *
 * @param int $id The tool type ID.
 * @param stdClass $course The course object.
 * @param moodle_url $returnurl The return URL in the tool consumer (TC) that the tool provider (TP)
 *                              will use to return the Content-Item message.
 * @param string $title The tool's title, if available.
 * @param string $text The text to display to represent the content item. This value may be a long description of the content item.
 * @param array $mediatypes Array of MIME types types supported by the TC. If empty, the TC will support mcodeltilink by default.
 * @param array $presentationtargets Array of ways in which the selected content item(s) can be requested to be opened
 *                                   (via the presentationDocumentTarget element for a returned content item).
 *                                   If empty, "frame", "iframe", and "window" will be supported by default.
 * @param bool $autocreate Indicates whether any content items returned by the TP would be automatically persisted without
 * @param bool $mumcodeltiple Indicates whether the user should be permitted to select more than one item. False by default.
 *                         any option for the user to cancel the operation. False by default.
 * @param bool $unsigned Indicates whether the TC is willing to accept an unsigned return message, or not.
 *                       A signed message should always be required when the content item is being created automatically in the
 *                       TC without further interaction from the user. False by default.
 * @param bool $canconfirm Flag for can_confirm parameter. False by default.
 * @param bool $copyadvice Indicates whether the TC is able and willing to make a local copy of a content item. False by default.
 * @return stdClass The object containing the signed request parameters and the URL to the TP's Content-Item selection interface.
 * @throws moodle_exception When the mcodelti tool type does not exist.`
 * @throws coding_exception For invalid media type and presentation target parameters.
 */
function mcodelti_build_content_item_selection_request($id, $course, moodle_url $returnurl, $title = '', $text = '', $mediatypes = [],
                                                  $presentationtargets = [], $autocreate = false, $mumcodeltiple = false,
                                                  $unsigned = false, $canconfirm = false, $copyadvice = false) {
    $tool = mcodelti_get_type($id);
    // Validate parameters.
    if (!$tool) {
        throw new moodle_exception('errortooltypenotfound', 'mod_mcodelti');
    }
    if (!is_array($mediatypes)) {
        throw new coding_exception('The list of accepted media types should be in an array');
    }
    if (!is_array($presentationtargets)) {
        throw new coding_exception('The list of accepted presentation targets should be in an array');
    }

    // Check title. If empty, use the tool's name.
    if (empty($title)) {
        $title = $tool->name;
    }

    $typeconfig = mcodelti_get_type_config($id);
    $key = '';
    $secret = '';
    $ismcodelti2 = false;
    if (isset($tool->toolproxyid)) {
        $ismcodelti2 = true;
        $toolproxy = mcodelti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $toolproxy = null;
        if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        }
        if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        }
    }
    $tool->enabledcapability = '';
    if (!empty($typeconfig['enabledcapability_ContentItemSelectionRequest'])) {
        $tool->enabledcapability = $typeconfig['enabledcapability_ContentItemSelectionRequest'];
    }

    $tool->parameter = '';
    if (!empty($typeconfig['parameter_ContentItemSelectionRequest'])) {
        $tool->parameter = $typeconfig['parameter_ContentItemSelectionRequest'];
    }

    // Set the tool URL.
    if (!empty($typeconfig['toolurl_ContentItemSelectionRequest'])) {
        $toolurl = new moodle_url($typeconfig['toolurl_ContentItemSelectionRequest']);
    } else {
        $toolurl = new moodle_url($typeconfig['toolurl']);
    }

    // Check if SSL is forced.
    if (!empty($typeconfig['forcessl'])) {
        // Make sure the tool URL is set to https.
        if (strtolower($toolurl->get_scheme()) === 'http') {
            $toolurl->set_scheme('https');
        }
        // Make sure the return URL is set to https.
        if (strtolower($returnurl->get_scheme()) === 'http') {
            $returnurl->set_scheme('https');
        }
    }
    $toolurlout = $toolurl->out(false);

    // Get base request parameters.
    $instance = new stdClass();
    $instance->course = $course->id;
    $requestparams = mcodelti_build_request($instance, $typeconfig, $course, $id, $ismcodelti2);

    // Get mcodelti2-specific request parameters and merge to the request parameters if applicable.
    if ($ismcodelti2) {
        $mcodelti2params = mcodelti_build_request_mcodelti2($tool, $requestparams);
        $requestparams = array_merge($requestparams, $mcodelti2params);
    }

    // Get standard request parameters and merge to the request parameters.
    $orgid = !empty($typeconfig['organizationid']) ? $typeconfig['organizationid'] : '';
    $standardparams = mcodelti_build_standard_request(null, $orgid, $ismcodelti2, 'ContentItemSelectionRequest');
    $requestparams = array_merge($requestparams, $standardparams);

    // Get custom request parameters and merge to the request parameters.
    $customstr = '';
    if (!empty($typeconfig['customparameters'])) {
        $customstr = $typeconfig['customparameters'];
    }
    $customparams = mcodelti_build_custom_parameters($toolproxy, $tool, $instance, $requestparams, $customstr, '', $ismcodelti2);
    $requestparams = array_merge($requestparams, $customparams);

    // Allow request params to be updated by sub-plugins.
    $plugins = core_component::get_plugin_list('mcodeltisource');
    foreach (array_keys($plugins) as $plugin) {
        $pluginparams = component_callback('mcodeltisource_' . $plugin, 'before_launch', [$instance, $toolurlout, $requestparams], []);

        if (!empty($pluginparams) && is_array($pluginparams)) {
            $requestparams = array_merge($requestparams, $pluginparams);
        }
    }

    // Media types. Set to mcodeltilink by default if empty.
    if (empty($mediatypes)) {
        $mediatypes = [
            'application/vnd.ims.mcodelti.v1.mcodeltilink',
        ];
    }
    $requestparams['accept_media_types'] = implode(',', $mediatypes);

    // Presentation targets. Supports frame, iframe, window by default if empty.
    if (empty($presentationtargets)) {
        $presentationtargets = [
            'frame',
            'iframe',
            'window',
        ];
    }
    $requestparams['accept_presentation_document_targets'] = implode(',', $presentationtargets);

    // Other request parameters.
    $requestparams['accept_copy_advice'] = $copyadvice === true ? 'true' : 'false';
    $requestparams['accept_mumcodeltiple'] = $mumcodeltiple === true ? 'true' : 'false';
    $requestparams['accept_unsigned'] = $unsigned === true ? 'true' : 'false';
    $requestparams['auto_create'] = $autocreate === true ? 'true' : 'false';
    $requestparams['can_confirm'] = $canconfirm === true ? 'true' : 'false';
    $requestparams['content_item_return_url'] = $returnurl->out(false);
    $requestparams['title'] = $title;
    $requestparams['text'] = $text;
    $signedparams = mcodelti_sign_parameters($requestparams, $toolurlout, 'POST', $key, $secret);
    $toolurlparams = $toolurl->params();

    // Strip querystring params in endpoint url from $signedparams to avoid duplication.
    if (!empty($toolurlparams) && !empty($signedparams)) {
        foreach (array_keys($toolurlparams) as $paramname) {
            if (isset($signedparams[$paramname])) {
                unset($signedparams[$paramname]);
            }
        }
    }

    // Check for params that should not be passed. Unset if they are set.
    $unwantedparams = [
        'resource_link_id',
        'resource_link_title',
        'resource_link_description',
        'launch_presentation_return_url',
        'lis_result_sourcedid',
    ];
    foreach ($unwantedparams as $param) {
        if (isset($signedparams[$param])) {
            unset($signedparams[$param]);
        }
    }

    // Prepare result object.
    $result = new stdClass();
    $result->params = $signedparams;
    $result->url = $toolurlout;

    return $result;
}

/**
 * Processes the tool provider's response to the ContentItemSelectionRequest and builds the configuration data from the
 * selected content item. This configuration data can be then used when adding a tool into the course.
 *
 * @param int $typeid The tool type ID.
 * @param string $messagetype The value for the mcodelti_message_type parameter.
 * @param string $mcodeltiversion The value for the mcodelti_version parameter.
 * @param string $consumerkey The consumer key.
 * @param string $contentitemsjson The JSON string for the content_items parameter.
 * @return stdClass The array of module information objects.
 * @throws moodle_exception
 * @throws mcodelti\OAuthException
 */
function mcodelti_tool_configuration_from_content_item($typeid, $messagetype, $mcodeltiversion, $consumerkey, $contentitemsjson) {
    $tool = mcodelti_get_type($typeid);
    // Validate parameters.
    if (!$tool) {
        throw new moodle_exception('errortooltypenotfound', 'mod_mcodelti');
    }
    // Check mcodelti_message_type. Show debugging if it's not set to ContentItemSelection.
    // No need to throw exceptions for now since mcodelti_message_type does not seem to be used in this processing at the moment.
    if ($messagetype !== 'ContentItemSelection') {
        debugging("mcodelti_message_type is invalid: {$messagetype}. It should be set to 'ContentItemSelection'.",
            DEBUG_DEVELOPER);
    }

    $typeconfig = mcodelti_get_type_config($typeid);

    if (isset($tool->toolproxyid)) {
        $ismcodelti2 = true;
        $toolproxy = mcodelti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $ismcodelti2 = false;
        $toolproxy = null;
        if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        } else {
            $key = '';
        }
        if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        } else {
            $secret = '';
        }
    }

    // Check mcodelti versions from our side and the response's side. Show debugging if they don't match.
    // No need to throw exceptions for now since mcodelti version does not seem to be used in this processing at the moment.
    $expectedversion = MCODELTI_VERSION_1;
    if ($ismcodelti2) {
        $expectedversion = MCODELTI_VERSION_2;
    }
    if ($mcodeltiversion !== $expectedversion) {
        debugging("mcodelti_version from response does not match the tool's configuration. Tool: {$expectedversion}," .
            " Response: {$mcodeltiversion}", DEBUG_DEVELOPER);
    }

    if ($consumerkey !== $key) {
        throw new moodle_exception('errorincorrectconsumerkey', 'mod_mcodelti');
    }

    $store = new mcodelti\TrivialOAuthDataStore();
    $store->add_consumer($key, $secret);
    $server = new mcodelti\OAuthServer($store);
    $method = new mcodelti\OAuthSignatureMethod_HMAC_SHA1();
    $server->add_signature_method($method);
    $request = mcodelti\OAuthRequest::from_request();
    try {
        $server->verify_request($request);
    } catch (mcodelti\OAuthException $e) {
        throw new mcodelti\OAuthException("OAuth signature failed: " . $e->getMessage());
    }

    $items = json_decode($contentitemsjson);
    if (empty($items)) {
        throw new moodle_exception('errorinvaliddata', 'mod_mcodelti', '', $contentitemsjson);
    }
    if ($items->{'@context'} !== 'http://purl.imsglobal.org/ctx/mcodelti/v1/ContentItem') {
        throw new moodle_exception('errorinvalidmediatype', 'mod_mcodelti', '', $items->{'@context'});
    }
    if (!isset($items->{'@graph'}) || !is_array($items->{'@graph'}) || (count($items->{'@graph'}) > 1)) {
        throw new moodle_exception('errorinvalidresponseformat', 'mod_mcodelti');
    }

    $config = null;
    if (!empty($items->{'@graph'})) {
        $item = $items->{'@graph'}[0];

        $config = new stdClass();
        $config->name = '';
        if (isset($item->title)) {
            $config->name = $item->title;
        }
        if (empty($config->name)) {
            $config->name = $tool->name;
        }
        if (isset($item->text)) {
            $config->introeditor = [
                'text' => $item->text,
                'format' => FORMAT_PLAIN
            ];
        }
        if (isset($item->icon->{'@id'})) {
            $iconurl = new moodle_url($item->icon->{'@id'});
            // Assign item's icon URL to secureicon or icon depending on its scheme.
            if (strtolower($iconurl->get_scheme()) === 'https') {
                $config->secureicon = $iconurl->out(false);
            } else {
                $config->icon = $iconurl->out(false);
            }
        }
        if (isset($item->url)) {
            $url = new moodle_url($item->url);
            $config->toolurl = $url->out(false);
            $config->typeid = 0;
        } else {
            $config->typeid = $typeid;
        }
        $config->instructorchoicesendname = MCODELTI_SETTING_NEVER;
        $config->instructorchoicesendemailaddr = MCODELTI_SETTING_NEVER;
        $config->instructorchoiceacceptgrades = MCODELTI_SETTING_NEVER;
        $config->launchcontainer = MCODELTI_LAUNCH_CONTAINER_DEFAULT;
        if (isset($item->placementAdvice->presentationDocumentTarget)) {
            if ($item->placementAdvice->presentationDocumentTarget === 'window') {
                $config->launchcontainer = MCODELTI_LAUNCH_CONTAINER_WINDOW;
            } else if ($item->placementAdvice->presentationDocumentTarget === 'frame') {
                $config->launchcontainer = MCODELTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;
            } else if ($item->placementAdvice->presentationDocumentTarget === 'iframe') {
                $config->launchcontainer = MCODELTI_LAUNCH_CONTAINER_EMBED;
            }
        }
        if (isset($item->custom)) {
            $customparameters = [];
            foreach ($item->custom as $key => $value) {
                $customparameters[] = "{$key}={$value}";
            }
            $config->instructorcustomparameters = implode("\n", $customparameters);
        }
    }
    return $config;
}

function mcodelti_get_tool_table($tools, $id) {
    global $CFG, $OUTPUT, $USER;
    $html = '';

    $typename = get_string('typename', 'mcodelti');
    $baseurl = get_string('baseurl', 'mcodelti');
    $action = get_string('action', 'mcodelti');
    $createdon = get_string('createdon', 'mcodelti');

    if (!empty($tools)) {
        $html .= "
        <div id=\"{$id}_tools_container\" style=\"margin-top:.5em;margin-bottom:.5em\">
            <table id=\"{$id}_tools\">
                <thead>
                    <tr>
                        <th>$typename</th>
                        <th>$baseurl</th>
                        <th>$createdon</th>
                        <th>$action</th>
                    </tr>
                </thead>
        ";

        foreach ($tools as $type) {
            $date = userdate($type->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $accept = get_string('accept', 'mcodelti');
            $update = get_string('update', 'mcodelti');
            $delete = get_string('delete', 'mcodelti');

            if (empty($type->toolproxyid)) {
                $baseurl = new \moodle_url('/mod/mcodelti/typessettings.php', array(
                        'action' => 'accept',
                        'id' => $type->id,
                        'sesskey' => sesskey(),
                        'tab' => $id
                    ));
                $ref = $type->baseurl;
            } else {
                $baseurl = new \moodle_url('/mod/mcodelti/toolssettings.php', array(
                        'action' => 'accept',
                        'id' => $type->id,
                        'sesskey' => sesskey(),
                        'tab' => $id
                    ));
                $ref = $type->tpname;
            }

            $accepthtml = $OUTPUT->action_icon($baseurl,
                    new \pix_icon('t/check', $accept, '', array('class' => 'iconsmall')), null,
                    array('title' => $accept, 'class' => 'editing_accept'));

            $deleteaction = 'delete';

            if ($type->state == MCODELTI_TOOL_STATE_CONFIGURED) {
                $accepthtml = '';
            }

            if ($type->state != MCODELTI_TOOL_STATE_REJECTED) {
                $deleteaction = 'reject';
                $delete = get_string('reject', 'mcodelti');
            }

            $updateurl = clone($baseurl);
            $updateurl->param('action', 'update');
            $updatehtml = $OUTPUT->action_icon($updateurl,
                    new \pix_icon('t/edit', $update, '', array('class' => 'iconsmall')), null,
                    array('title' => $update, 'class' => 'editing_update'));

            if (($type->state != MCODELTI_TOOL_STATE_REJECTED) || empty($type->toolproxyid)) {
                $deleteurl = clone($baseurl);
                $deleteurl->param('action', $deleteaction);
                $deletehtml = $OUTPUT->action_icon($deleteurl,
                        new \pix_icon('t/delete', $delete, '', array('class' => 'iconsmall')), null,
                        array('title' => $delete, 'class' => 'editing_delete'));
            } else {
                $deletehtml = '';
            }
            $html .= "
            <tr>
                <td>
                    {$type->name}
                </td>
                <td>
                    {$ref}
                </td>
                <td>
                    {$date}
                </td>
                <td align=\"center\">
                    {$accepthtml}{$updatehtml}{$deletehtml}
                </td>
            </tr>
            ";
        }
        $html .= '</table></div>';
    } else {
        $html .= get_string('no_' . $id, 'mcodelti');
    }

    return $html;
}

/**
 * This function builds the tab for a category of tool proxies
 *
 * @param object    $toolproxies    Tool proxy instance objects
 * @param string    $id             Category ID
 *
 * @return string                   HTML for tab
 */
function mcodelti_get_tool_proxy_table($toolproxies, $id) {
    global $OUTPUT;

    if (!empty($toolproxies)) {
        $typename = get_string('typename', 'mcodelti');
        $url = get_string('registrationurl', 'mcodelti');
        $action = get_string('action', 'mcodelti');
        $createdon = get_string('createdon', 'mcodelti');

        $html = <<< EOD
        <div id="{$id}_tool_proxies_container" style="margin-top: 0.5em; margin-bottom: 0.5em">
            <table id="{$id}_tool_proxies">
                <thead>
                    <tr>
                        <th>{$typename}</th>
                        <th>{$url}</th>
                        <th>{$createdon}</th>
                        <th>{$action}</th>
                    </tr>
                </thead>
EOD;
        foreach ($toolproxies as $toolproxy) {
            $date = userdate($toolproxy->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $accept = get_string('register', 'mcodelti');
            $update = get_string('update', 'mcodelti');
            $delete = get_string('delete', 'mcodelti');

            $baseurl = new \moodle_url('/mod/mcodelti/registersettings.php', array(
                    'action' => 'accept',
                    'id' => $toolproxy->id,
                    'sesskey' => sesskey(),
                    'tab' => $id
                ));

            $registerurl = new \moodle_url('/mod/mcodelti/register.php', array(
                    'id' => $toolproxy->id,
                    'sesskey' => sesskey(),
                    'tab' => 'tool_proxy'
                ));

            $accepthtml = $OUTPUT->action_icon($registerurl,
                    new \pix_icon('t/check', $accept, '', array('class' => 'iconsmall')), null,
                    array('title' => $accept, 'class' => 'editing_accept'));

            $deleteaction = 'delete';

            if ($toolproxy->state != MCODELTI_TOOL_PROXY_STATE_CONFIGURED) {
                $accepthtml = '';
            }

            if (($toolproxy->state == MCODELTI_TOOL_PROXY_STATE_CONFIGURED) || ($toolproxy->state == MCODELTI_TOOL_PROXY_STATE_PENDING)) {
                $delete = get_string('cancel', 'mcodelti');
            }

            $updateurl = clone($baseurl);
            $updateurl->param('action', 'update');
            $updatehtml = $OUTPUT->action_icon($updateurl,
                    new \pix_icon('t/edit', $update, '', array('class' => 'iconsmall')), null,
                    array('title' => $update, 'class' => 'editing_update'));

            $deleteurl = clone($baseurl);
            $deleteurl->param('action', $deleteaction);
            $deletehtml = $OUTPUT->action_icon($deleteurl,
                    new \pix_icon('t/delete', $delete, '', array('class' => 'iconsmall')), null,
                    array('title' => $delete, 'class' => 'editing_delete'));
            $html .= <<< EOD
            <tr>
                <td>
                    {$toolproxy->name}
                </td>
                <td>
                    {$toolproxy->regurl}
                </td>
                <td>
                    {$date}
                </td>
                <td align="center">
                    {$accepthtml}{$updatehtml}{$deletehtml}
                </td>
            </tr>
EOD;
        }
        $html .= '</table></div>';
    } else {
        $html = get_string('no_' . $id, 'mcodelti');
    }

    return $html;
}

/**
 * Extracts the enabled capabilities into an array, including those implicitly declared in a parameter
 *
 * @param object    $tool           Tool instance object
 *
 * @return Array of enabled capabilities
 */
function mcodelti_get_enabled_capabilities($tool) {
    if (!empty($tool->enabledcapability)) {
        $enabledcapabilities = explode("\n", $tool->enabledcapability);
    } else {
        $enabledcapabilities = array();
    }
    $paramstr = str_replace("\r\n", "\n", $tool->parameter);
    $paramstr = str_replace("\n\r", "\n", $paramstr);
    $paramstr = str_replace("\r", "\n", $paramstr);
    $params = explode("\n", $paramstr);
    foreach ($params as $param) {
        $pos = strpos($param, '=');
        if (($pos === false) || ($pos < 1)) {
            continue;
        }
        $value = trim(core_text::substr($param, $pos + 1, strlen($param)));
        if (substr($value, 0, 1) == '$') {
            $value = substr($value, 1);
            if (!in_array($value, $enabledcapabilities)) {
                $enabledcapabilities[] = $value;
            }
        }
    }
    return $enabledcapabilities;
}

/**
 * Splits the custom parameters field to the various parameters
 *
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param array     $params         mcodelti launch parameters
 * @param string    $customstr      String containing the parameters
 * @param boolean   $ismcodelti2         True if an mcodelti 2 tool is being launched
 *
 * @return array of custom parameters
 */
function mcodelti_split_custom_parameters($toolproxy, $tool, $params, $customstr, $ismcodelti2 = false) {
    $customstr = str_replace("\r\n", "\n", $customstr);
    $customstr = str_replace("\n\r", "\n", $customstr);
    $customstr = str_replace("\r", "\n", $customstr);
    $lines = explode("\n", $customstr);  // Or should this split on "/[\n;]/"?
    $retval = array();
    foreach ($lines as $line) {
        $pos = strpos($line, '=');
        if ( $pos === false || $pos < 1 ) {
            continue;
        }
        $key = trim(core_text::substr($line, 0, $pos));
        $key = mcodelti_map_keyname($key, false);
        $val = trim(core_text::substr($line, $pos + 1, strlen($line)));
        $val = mcodelti_parse_custom_parameter($toolproxy, $tool, $params, $val, $ismcodelti2);
        $key2 = mcodelti_map_keyname($key);
        $retval['custom_'.$key2] = $val;
        if ($key != $key2) {
            $retval['custom_'.$key] = $val;
        }
    }
    return $retval;
}

/**
 * Adds the custom parameters to an array
 *
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param array     $params         mcodelti launch parameters
 * @param array     $parameters     Array containing the parameters
 *
 * @return array    Array of custom parameters
 */
function mcodelti_get_custom_parameters($toolproxy, $tool, $params, $parameters) {
    $retval = array();
    foreach ($parameters as $key => $val) {
        $key2 = mcodelti_map_keyname($key);
        $val = mcodelti_parse_custom_parameter($toolproxy, $tool, $params, $val, true);
        $retval['custom_'.$key2] = $val;
        if ($key != $key2) {
            $retval['custom_'.$key] = $val;
        }
    }
    return $retval;
}

/**
 * Parse a custom parameter to replace any substitution variables
 *
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param array     $params         mcodelti launch parameters
 * @param string    $value          Custom parameter value
 * @param boolean   $ismcodelti2         True if an mcodelti 2 tool is being launched
 *
 * @return Parsed value of custom parameter
 */
function mcodelti_parse_custom_parameter($toolproxy, $tool, $params, $value, $ismcodelti2) {
    global $USER, $COURSE;

    if ($value) {
        if (substr($value, 0, 1) == '\\') {
            $value = substr($value, 1);
        } else if (substr($value, 0, 1) == '$') {
            $value1 = substr($value, 1);
            $enabledcapabilities = mcodelti_get_enabled_capabilities($tool);
            if (!$ismcodelti2 || in_array($value1, $enabledcapabilities)) {
                $capabilities = mcodelti_get_capabilities();
                if (array_key_exists($value1, $capabilities)) {
                    $val = $capabilities[$value1];
                    if ($val) {
                        if (substr($val, 0, 1) != '$') {
                            $value = $params[$val];
                        } else {
                            $valarr = explode('->', substr($val, 1), 2);
                            $value = "{${$valarr[0]}->{$valarr[1]}}";
                            $value = str_replace('<br />' , ' ', $value);
                            $value = str_replace('<br>' , ' ', $value);
                            $value = format_string($value);
                        }
                    } else {
                        $value = mcodelti_calculate_custom_parameter($value1);
                    }
                } else if ($ismcodelti2) {
                    $val = $value;
                    $services = mcodelti_get_services();
                    foreach ($services as $service) {
                        $service->set_tool_proxy($toolproxy);
                        $value = $service->parse_value($val);
                        if ($val != $value) {
                            break;
                        }
                    }
                }
            }
        }
    }
    return $value;
}

/**
 * Calculates the value of a custom parameter that has not been specified earlier
 *
 * @param string    $value          Custom parameter value
 *
 * @return string Calculated value of custom parameter
 */
function mcodelti_calculate_custom_parameter($value) {
    global $USER, $COURSE;

    switch ($value) {
        case 'Moodle.Person.userGroupIds':
            return implode(",", groups_get_user_groups($COURSE->id, $USER->id)[0]);
    }
    return null;
}

/**
 * Used for building the names of the different custom parameters
 *
 * @param string $key   Parameter name
 * @param bool $tolower Do we want to convert the key into lower case?
 * @return string       Processed name
 */
function mcodelti_map_keyname($key, $tolower = true) {
    $newkey = "";
    if ($tolower) {
        $key = core_text::strtolower(trim($key));
    }
    foreach (str_split($key) as $ch) {
        if ( ($ch >= 'a' && $ch <= 'z') || ($ch >= '0' && $ch <= '9') || (!$tolower && ($ch >= 'A' && $ch <= 'Z'))) {
            $newkey .= $ch;
        } else {
            $newkey .= '_';
        }
    }
    return $newkey;
}

/**
 * Gets the IMS role string for the specified user and mcodelti course module.
 *
 * @param mixed    $user      User object or user id
 * @param int      $cmid      The course module id of the mcodelti activity
 * @param int      $courseid  The course id of the mcodelti activity
 * @param boolean  $ismcodelti2    True if an mcodelti 2 tool is being launched
 *
 * @return string A role string suitable for passing with an mcodelti launch
 */
function mcodelti_get_ims_role($user, $cmid, $courseid, $ismcodelti2) {
    $roles = array();

    if (empty($cmid)) {
        // If no cmid is passed, check if the user is a teacher in the course
        // This allows other modules to programmatically "fake" a launch without
        // a real mcodelti instance.
        $context = context_course::instance($courseid);

        if (has_capability('moodle/course:manageactivities', $context, $user)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    } else {
        $context = context_module::instance($cmid);

        if (has_capability('mod/mcodelti:manage', $context)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    }

    if (is_siteadmin($user) || has_capability('mod/mcodelti:admin', $context)) {
        // Make sure admins do not have the Learner role, then set admin role.
        $roles = array_diff($roles, array('Learner'));
        if (!$ismcodelti2) {
            array_push($roles, 'urn:mcodelti:sysrole:ims/lis/Administrator', 'urn:mcodelti:instrole:ims/lis/Administrator');
        } else {
            array_push($roles, 'http://purl.imsglobal.org/vocab/lis/v2/person#Administrator');
        }
    }

    return join(',', $roles);
}

/**
 * Returns configuration details for the tool
 *
 * @param int $typeid   Basic mcodelti tool typeid
 *
 * @return array        Tool Configuration
 */
function mcodelti_get_type_config($typeid) {
    global $DB;

    $query = "SELECT name, value
                FROM {mcodelti_types_config}
               WHERE typeid = :typeid1
           UNION ALL
              SELECT 'toolurl' AS name, baseurl AS value
                FROM {mcodelti_types}
               WHERE id = :typeid2
           UNION ALL
              SELECT 'icon' AS name, icon AS value
                FROM {mcodelti_types}
               WHERE id = :typeid3
           UNION ALL
              SELECT 'secureicon' AS name, secureicon AS value
                FROM {mcodelti_types}
               WHERE id = :typeid4";

    $typeconfig = array();
    $configs = $DB->get_records_sql($query,
        array('typeid1' => $typeid, 'typeid2' => $typeid, 'typeid3' => $typeid, 'typeid4' => $typeid));

    if (!empty($configs)) {
        foreach ($configs as $config) {
            $typeconfig[$config->name] = $config->value;
        }
    }

    return $typeconfig;
}

function mcodelti_get_tools_by_url($url, $state, $courseid = null) {
    $domain = mcodelti_get_domain_from_url($url);

    return mcodelti_get_tools_by_domain($domain, $state, $courseid);
}

function mcodelti_get_tools_by_domain($domain, $state = null, $courseid = null) {
    global $DB, $SITE;

    $filters = array('tooldomain' => $domain);

    $statefilter = '';
    $coursefilter = '';

    if ($state) {
        $statefilter = 'AND state = :state';
    }

    if ($courseid && $courseid != $SITE->id) {
        $coursefilter = 'OR course = :courseid';
    }

    $query = "SELECT *
                FROM {mcodelti_types}
               WHERE tooldomain = :tooldomain
                 AND (course = :siteid $coursefilter)
                 $statefilter";

    return $DB->get_records_sql($query, array(
        'courseid' => $courseid,
        'siteid' => $SITE->id,
        'tooldomain' => $domain,
        'state' => $state
    ));
}

/**
 * Returns all basicmcodelti tools configured by the administrator
 *
 */
function mcodelti_filter_get_types($course) {
    global $DB;

    if (!empty($course)) {
        $where = "WHERE t.course = :course";
        $params = array('course' => $course);
    } else {
        $where = '';
        $params = array();
    }
    $query = "SELECT t.id, t.name, t.baseurl, t.state, t.toolproxyid, t.timecreated, tp.name tpname
                FROM {mcodelti_types} t LEFT OUTER JOIN {mcodelti_tool_proxies} tp ON t.toolproxyid = tp.id
                {$where}";
    return $DB->get_records_sql($query, $params);
}

/**
 * Given an array of tools, filter them based on their state
 *
 * @param array $tools An array of mcodelti_types records
 * @param int $state One of the mcodelti_TOOL_STATE_* constants
 * @return array
 */
function mcodelti_filter_tool_types(array $tools, $state) {
    $return = array();
    foreach ($tools as $key => $tool) {
        if ($tool->state == $state) {
            $return[$key] = $tool;
        }
    }
    return $return;
}

/**
 * Returns all mcodelti types visible in this course
 *
 * @param int $courseid The id of the course to retieve types for
 * @param array $coursevisible options for 'coursevisible' field,
 *        default [mcodelti_COURSEVISIBLE_PRECONFIGURED, mcodelti_COURSEVISIBLE_ACTIVITYCHOOSER]
 * @return stdClass[] All the mcodelti types visible in the given course
 */
function mcodelti_get_mcodelti_types_by_course($courseid, $coursevisible = null) {
    global $DB, $SITE;

    if ($coursevisible === null) {
        $coursevisible = [MCODELTI_COURSEVISIBLE_PRECONFIGURED, MCODELTI_COURSEVISIBLE_ACTIVITYCHOOSER];
    }

    list($coursevisiblesql, $coursevisparams) = $DB->get_in_or_equal($coursevisible, SQL_PARAMS_NAMED, 'coursevisible');
    $query = "SELECT *
                FROM {mcodelti_types}
               WHERE coursevisible $coursevisiblesql
                 AND (course = :siteid OR course = :courseid)
                 AND state = :active";

    return $DB->get_records_sql($query,
        array('siteid' => $SITE->id, 'courseid' => $courseid, 'active' => MCODELTI_TOOL_STATE_CONFIGURED) + $coursevisparams);
}

/**
 * Returns tool types for mcodelti add instance and edit page
 *
 * @return array Array of mcodelti types
 */
function mcodelti_get_types_for_add_instance() {
    global $COURSE;
    $admintypes = mcodelti_get_mcodelti_types_by_course($COURSE->id);

    $types = array();
    $types[0] = (object)array('name' => get_string('automatic', 'mcodelti'), 'course' => 0, 'toolproxyid' => null);

    foreach ($admintypes as $type) {
        $types[$type->id] = $type;
    }

    return $types;
}

/**
 * Returns a list of configured types in the given course
 *
 * @param int $courseid The id of the course to retieve types for
 * @param int $sectionreturn section to return to for forming the URLs
 * @return array Array of mcodelti types. Each element is object with properties: name, title, icon, help, helplink, link
 */
function mcodelti_get_configured_types($courseid, $sectionreturn = 0) {
    global $OUTPUT;
    $types = array();
    $admintypes = mcodelti_get_mcodelti_types_by_course($courseid, [MCODELTI_COURSEVISIBLE_ACTIVITYCHOOSER]);

    foreach ($admintypes as $mcodeltitype) {
        $type           = new stdClass();
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->name     = 'mcodelti_type_' . $mcodeltitype->id;
        // Clean the name. We don't want tags here.
        $type->title    = clean_param($mcodeltitype->name, PARAM_NOTAGS);
        $trimmeddescription = trim($mcodeltitype->description);
        if ($trimmeddescription != '') {
            // Clean the description. We don't want tags here.
            $type->help     = clean_param($trimmeddescription, PARAM_NOTAGS);
            $type->helplink = get_string('modulename_shortcut_link', 'mcodelti');
        }
        if (empty($mcodeltitype->icon)) {
            $type->icon = $OUTPUT->pix_icon('icon', '', 'mcodelti', array('class' => 'icon'));
        } else {
            $type->icon = html_writer::empty_tag('img', array('src' => $mcodeltitype->icon, 'alt' => $mcodeltitype->name, 'class' => 'icon'));
        }
        $type->link = new moodle_url('/course/modedit.php', array('add' => 'mcodelti', 'return' => 0, 'course' => $courseid,
            'sr' => $sectionreturn, 'typeid' => $mcodeltitype->id));
        $types[] = $type;
    }
    return $types;
}

function mcodelti_get_domain_from_url($url) {
    $matches = array();

    if (preg_match(MCODELTI_URL_DOMAIN_REGEX, $url, $matches)) {
        return $matches[1];
    }
}

function mcodelti_get_tool_by_url_match($url, $courseid = null, $state = MCODELTI_TOOL_STATE_CONFIGURED) {
    $possibletools = mcodelti_get_tools_by_url($url, $state, $courseid);

    return mcodelti_get_best_tool_by_url($url, $possibletools, $courseid);
}

function mcodelti_get_url_thumbprint($url) {
    // Parse URL requires a schema otherwise everything goes into 'path'.  Fixed 5.4.7 or later.
    if (preg_match('/https?:\/\//', $url) !== 1) {
        $url = 'http://'.$url;
    }
    $urlparts = parse_url(strtolower($url));
    if (!isset($urlparts['path'])) {
        $urlparts['path'] = '';
    }

    if (!isset($urlparts['query'])) {
        $urlparts['query'] = '';
    }

    if (!isset($urlparts['host'])) {
        $urlparts['host'] = '';
    }

    if (substr($urlparts['host'], 0, 4) === 'www.') {
        $urlparts['host'] = substr($urlparts['host'], 4);
    }

    $urllower = $urlparts['host'] . '/' . $urlparts['path'];

    if ($urlparts['query'] != '') {
        $urllower .= '?' . $urlparts['query'];
    }

    return $urllower;
}

function mcodelti_get_best_tool_by_url($url, $tools, $courseid = null) {
    if (count($tools) === 0) {
        return null;
    }

    $urllower = mcodelti_get_url_thumbprint($url);

    foreach ($tools as $tool) {
        $tool->_matchscore = 0;

        $toolbaseurllower = mcodelti_get_url_thumbprint($tool->baseurl);

        if ($urllower === $toolbaseurllower) {
            // 100 points for exact thumbprint match.
            $tool->_matchscore += 100;
        } else if (substr($urllower, 0, strlen($toolbaseurllower)) === $toolbaseurllower) {
            // 50 points if tool thumbprint starts with the base URL thumbprint.
            $tool->_matchscore += 50;
        }

        // Prefer course tools over site tools.
        if (!empty($courseid)) {
            // Minus 10 points for not matching the course id (global tools).
            if ($tool->course != $courseid) {
                $tool->_matchscore -= 10;
            }
        }
    }

    $bestmatch = array_reduce($tools, function($value, $tool) {
        if ($tool->_matchscore > $value->_matchscore) {
            return $tool;
        } else {
            return $value;
        }

    }, (object)array('_matchscore' => -1));

    // None of the tools are suitable for this URL.
    if ($bestmatch->_matchscore <= 0) {
        return null;
    }

    return $bestmatch;
}

function mcodelti_get_shared_secrets_by_key($key) {
    global $DB;

    // Look up the shared secret for the specified key in both the types_config table (for configured tools)
    // And in the mcodelti resource table for ad-hoc tools.
    $query = "SELECT t2.value
                FROM {mcodelti_types_config} t1
                JOIN {mcodelti_types_config} t2 ON t1.typeid = t2.typeid
                JOIN {mcodelti_types} type ON t2.typeid = type.id
              WHERE t1.name = 'resourcekey'
                AND t1.value = :key1
                AND t2.name = 'password'
                AND type.state = :configured1
               UNION
              SELECT tp.secret AS value
                FROM {mcodelti_tool_proxies} tp
                JOIN {mcodelti_types} t ON tp.id = t.toolproxyid
              WHERE tp.guid = :key2
                AND t.state = :configured2
              UNION
             SELECT password AS value
               FROM {mcodelti}
              WHERE resourcekey = :key3";

    $sharedsecrets = $DB->get_records_sql($query, array('configured1' => MCODELTI_TOOL_STATE_CONFIGURED,
        'configured2' => MCODELTI_TOOL_STATE_CONFIGURED, 'key1' => $key, 'key2' => $key, 'key3' => $key));

    $values = array_map(function($item) {
        return $item->value;
    }, $sharedsecrets);

    // There should really only be one shared secret per key. But, we can't prevent
    // more than one getting entered. For instance, if the same key is used for two tool providers.
    return $values;
}

/**
 * Delete a Basic mcodelti configuration
 *
 * @param int $id   Configuration id
 */
function mcodelti_delete_type($id) {
    global $DB;

    // We should probably just copy the launch URL to the tool instances in this case... using a single query.
    /*
    $instances = $DB->get_records('mcodelti', array('typeid' => $id));
    foreach ($instances as $instance) {
        $instance->typeid = 0;
        $DB->update_record('mcodelti', $instance);
    }*/

    $DB->delete_records('mcodelti_types', array('id' => $id));
    $DB->delete_records('mcodelti_types_config', array('typeid' => $id));
}

function mcodelti_set_state_for_type($id, $state) {
    global $DB;

    $DB->update_record('mcodelti_types', array('id' => $id, 'state' => $state));
}

/**
 * Transforms a basic mcodelti object to an array
 *
 * @param object $mcodeltiobject    Basic mcodelti object
 *
 * @return array Basic mcodelti configuration details
 */
function mcodelti_get_config($mcodeltiobject) {
    $typeconfig = array();
    $typeconfig = (array)$mcodeltiobject;
    $additionalconfig = mcodelti_get_type_config($mcodeltiobject->typeid);
    $typeconfig = array_merge($typeconfig, $additionalconfig);
    return $typeconfig;
}

/**
 *
 * Generates some of the tool configuration based on the instance details
 *
 * @param int $id
 *
 * @return Instance configuration
 *
 */
function mcodelti_get_type_config_from_instance($id) {
    global $DB;

    $instance = $DB->get_record('mcodelti', array('id' => $id));
    $config = mcodelti_get_config($instance);

    $type = new \stdClass();
    $type->mcodelti_fix = $id;
    if (isset($config['toolurl'])) {
        $type->mcodelti_toolurl = $config['toolurl'];
    }
    if (isset($config['instructorchoicesendname'])) {
        $type->mcodelti_sendname = $config['instructorchoicesendname'];
    }
    if (isset($config['instructorchoicesendemailaddr'])) {
        $type->mcodelti_sendemailaddr = $config['instructorchoicesendemailaddr'];
    }
    if (isset($config['instructorchoiceacceptgrades'])) {
        $type->mcodelti_acceptgrades = $config['instructorchoiceacceptgrades'];
    }
    if (isset($config['instructorchoiceallowroster'])) {
        $type->mcodelti_allowroster = $config['instructorchoiceallowroster'];
    }

    if (isset($config['instructorcustomparameters'])) {
        $type->mcodelti_allowsetting = $config['instructorcustomparameters'];
    }
    return $type;
}

/**
 * Generates some of the tool configuration based on the admin configuration details
 *
 * @param int $id
 *
 * @return Configuration details
 */
function mcodelti_get_type_type_config($id) {
    global $DB;

    $basicmcodeltitype = $DB->get_record('mcodelti_types', array('id' => $id));
    $config = mcodelti_get_type_config($id);

    $type = new \stdClass();

    $type->mcodelti_typename = $basicmcodeltitype->name;

    $type->typeid = $basicmcodeltitype->id;

    $type->toolproxyid = $basicmcodeltitype->toolproxyid;

    $type->mcodelti_toolurl = $basicmcodeltitype->baseurl;

    $type->mcodelti_description = $basicmcodeltitype->description;

    $type->mcodelti_parameters = $basicmcodeltitype->parameter;

    $type->mcodelti_icon = $basicmcodeltitype->icon;

    $type->mcodelti_secureicon = $basicmcodeltitype->secureicon;

    if (isset($config['resourcekey'])) {
        $type->mcodelti_resourcekey = $config['resourcekey'];
    }
    if (isset($config['password'])) {
        $type->mcodelti_password = $config['password'];
    }

    if (isset($config['sendname'])) {
        $type->mcodelti_sendname = $config['sendname'];
    }
    if (isset($config['instructorchoicesendname'])) {
        $type->mcodelti_instructorchoicesendname = $config['instructorchoicesendname'];
    }
    if (isset($config['sendemailaddr'])) {
        $type->mcodelti_sendemailaddr = $config['sendemailaddr'];
    }
    if (isset($config['instructorchoicesendemailaddr'])) {
        $type->mcodelti_instructorchoicesendemailaddr = $config['instructorchoicesendemailaddr'];
    }
    if (isset($config['acceptgrades'])) {
        $type->mcodelti_acceptgrades = $config['acceptgrades'];
    }
    if (isset($config['instructorchoiceacceptgrades'])) {
        $type->mcodelti_instructorchoiceacceptgrades = $config['instructorchoiceacceptgrades'];
    }
    if (isset($config['allowroster'])) {
        $type->mcodelti_allowroster = $config['allowroster'];
    }
    if (isset($config['instructorchoiceallowroster'])) {
        $type->mcodelti_instructorchoiceallowroster = $config['instructorchoiceallowroster'];
    }

    if (isset($config['customparameters'])) {
        $type->mcodelti_customparameters = $config['customparameters'];
    }

    if (isset($config['forcessl'])) {
        $type->mcodelti_forcessl = $config['forcessl'];
    }

    if (isset($config['organizationid'])) {
        $type->mcodelti_organizationid = $config['organizationid'];
    }
    if (isset($config['organizationurl'])) {
        $type->mcodelti_organizationurl = $config['organizationurl'];
    }
    if (isset($config['organizationdescr'])) {
        $type->mcodelti_organizationdescr = $config['organizationdescr'];
    }
    if (isset($config['launchcontainer'])) {
        $type->mcodelti_launchcontainer = $config['launchcontainer'];
    }

    if (isset($config['coursevisible'])) {
        $type->mcodelti_coursevisible = $config['coursevisible'];
    }

    if (isset($config['contentitem'])) {
        $type->mcodelti_contentitem = $config['contentitem'];
    }

    if (isset($config['debuglaunch'])) {
        $type->mcodelti_debuglaunch = $config['debuglaunch'];
    }

    if (isset($config['module_class_type'])) {
        $type->mcodelti_module_class_type = $config['module_class_type'];
    }

    return $type;
}

function mcodelti_prepare_type_for_save($type, $config) {
    if (isset($config->mcodelti_toolurl)) {
        $type->baseurl = $config->mcodelti_toolurl;
        $type->tooldomain = mcodelti_get_domain_from_url($config->mcodelti_toolurl);
    }
    if (isset($config->mcodelti_description)) {
        $type->description = $config->mcodelti_description;
    }
    if (isset($config->mcodelti_typename)) {
        $type->name = $config->mcodelti_typename;
    }
    if (isset($config->mcodelti_coursevisible)) {
        $type->coursevisible = $config->mcodelti_coursevisible;
    }

    if (isset($config->mcodelti_icon)) {
        $type->icon = $config->mcodelti_icon;
    }
    if (isset($config->mcodelti_secureicon)) {
        $type->secureicon = $config->mcodelti_secureicon;
    }

    $type->forcessl = !empty($config->mcodelti_forcessl) ? $config->mcodelti_forcessl : 0;
    $config->mcodelti_forcessl = $type->forcessl;
    if (isset($config->mcodelti_contentitem)) {
        $type->contentitem = !empty($config->mcodelti_contentitem) ? $config->mcodelti_contentitem : 0;
        $config->mcodelti_contentitem = $type->contentitem;
    }

    $type->timemodified = time();

    unset ($config->mcodelti_typename);
    unset ($config->mcodelti_toolurl);
    unset ($config->mcodelti_description);
    unset ($config->mcodelti_icon);
    unset ($config->mcodelti_secureicon);
}

function mcodelti_update_type($type, $config) {
    global $DB, $CFG;

    mcodelti_prepare_type_for_save($type, $config);

    $clearcache = false;
    if (mcodelti_request_is_using_ssl() && !empty($type->secureicon)) {
        $clearcache = !isset($config->oldicon) || ($config->oldicon !== $type->secureicon);
    } else {
        $clearcache = isset($type->icon) && (!isset($config->oldicon) || ($config->oldicon !== $type->icon));
    }
    unset($config->oldicon);

    if ($DB->update_record('mcodelti_types', $type)) {
        foreach ($config as $key => $value) {
            if (substr($key, 0, 4) == 'mcodelti_' && !is_null($value)) {
                $record = new \StdClass();
                $record->typeid = $type->id;
                $record->name = substr($key, 4);
                $record->value = $value;
                mcodelti_update_config($record);
            }
        }
        require_once($CFG->libdir.'/modinfolib.php');
        if ($clearcache) {
            $sql = "SELECT DISTINCT course
                      FROM {mcodelti}
                     WHERE typeid = ?";

            $courses = $DB->get_fieldset_sql($sql, array($type->id));

            foreach ($courses as $courseid) {
                rebuild_course_cache($courseid, true);
            }
        }
    }
}

function mcodelti_add_type($type, $config) {
    global $USER, $SITE, $DB;

    mcodelti_prepare_type_for_save($type, $config);

    if (!isset($type->state)) {
        $type->state = MCODELTI_TOOL_STATE_PENDING;
    }

    if (!isset($type->timecreated)) {
        $type->timecreated = time();
    }

    if (!isset($type->createdby)) {
        $type->createdby = $USER->id;
    }

    if (!isset($type->course)) {
        $type->course = $SITE->id;
    }

    // Create a salt value to be used for signing passed data to extension services
    // The outcome service uses the service salt on the instance. This can be used
    // for communication with services not related to a specific mcodelti instance.
    $config->mcodelti_servicesalt = uniqid('', true);

    $id = $DB->insert_record('mcodelti_types', $type);

    if ($id) {
        foreach ($config as $key => $value) {
            if (substr($key, 0, 4) == 'mcodelti_' && !is_null($value)) {
                $record = new \StdClass();
                $record->typeid = $id;
                $record->name = substr($key, 4);
                $record->value = $value;

                mcodelti_add_config($record);
            }
        }
    }

    return $id;
}

/**
 * Given an array of tool proxies, filter them based on their state
 *
 * @param array $toolproxies An array of mcodelti_tool_proxies records
 * @param int $state One of the mcodelti_TOOL_PROXY_STATE_* constants
 *
 * @return array
 */
function mcodelti_filter_tool_proxy_types(array $toolproxies, $state) {
    $return = array();
    foreach ($toolproxies as $key => $toolproxy) {
        if ($toolproxy->state == $state) {
            $return[$key] = $toolproxy;
        }
    }
    return $return;
}

/**
 * Get the tool proxy instance given its GUID
 *
 * @param string  $toolproxyguid   Tool proxy GUID value
 *
 * @return object
 */
function mcodelti_get_tool_proxy_from_guid($toolproxyguid) {
    global $DB;

    $toolproxy = $DB->get_record('mcodelti_tool_proxies', array('guid' => $toolproxyguid));

    return $toolproxy;
}

/**
 * Get the tool proxy instance given its registration URL
 *
 * @param string $regurl Tool proxy registration URL
 *
 * @return array The record of the tool proxy with this url
 */
function mcodelti_get_tool_proxies_from_registration_url($regurl) {
    global $DB;

    return $DB->get_records_sql(
        'SELECT * FROM {mcodelti_tool_proxies}
        WHERE '.$DB->sql_compare_text('regurl', 256).' = :regurl',
        array('regurl' => $regurl)
    );
}

/**
 * Generates some of the tool proxy configuration based on the admin configuration details
 *
 * @param int $id
 *
 * @return Tool Proxy details
 */
function mcodelti_get_tool_proxy($id) {
    global $DB;

    $toolproxy = $DB->get_record('mcodelti_tool_proxies', array('id' => $id));
    return $toolproxy;
}

/**
 * Returns mcodelti tool proxies.
 *
 * @param bool $orphanedonly Only retrieves tool proxies that have no type associated with them
 * @return array of basicmcodelti types
 */
function mcodelti_get_tool_proxies($orphanedonly) {
    global $DB;

    if ($orphanedonly) {
        $tools = $DB->get_records('mcodelti_types');
        $usedproxyids = array_values($DB->get_fieldset_select('mcodelti_types', 'toolproxyid', 'toolproxyid IS NOT NULL'));
        $proxies = $DB->get_records('mcodelti_tool_proxies', null, 'state DESC, timemodified DESC');
        foreach ($proxies as $key => $value) {
            if (in_array($value->id, $usedproxyids)) {
                unset($proxies[$key]);
            }
        }
        return $proxies;
    } else {
        return $DB->get_records('mcodelti_tool_proxies', null, 'state DESC, timemodified DESC');
    }
}

/**
 * Generates some of the tool proxy configuration based on the admin configuration details
 *
 * @param int $id
 *
 * @return Tool Proxy details
 */
function mcodelti_get_tool_proxy_config($id) {
    $toolproxy = mcodelti_get_tool_proxy($id);

    $tp = new \stdClass();
    $tp->mcodelti_registrationname = $toolproxy->name;
    $tp->toolproxyid = $toolproxy->id;
    $tp->state = $toolproxy->state;
    $tp->mcodelti_registrationurl = $toolproxy->regurl;
    $tp->mcodelti_capabilities = explode("\n", $toolproxy->capabilityoffered);
    $tp->mcodelti_services = explode("\n", $toolproxy->serviceoffered);

    return $tp;
}

/**
 * Update the database with a tool proxy instance
 *
 * @param object   $config    Tool proxy definition
 *
 * @return int  Record id number
 */
function mcodelti_add_tool_proxy($config) {
    global $USER, $DB;

    $toolproxy = new \stdClass();
    if (isset($config->mcodelti_registrationname)) {
        $toolproxy->name = trim($config->mcodelti_registrationname);
    }
    if (isset($config->mcodelti_registrationurl)) {
        $toolproxy->regurl = trim($config->mcodelti_registrationurl);
    }
    if (isset($config->mcodelti_capabilities)) {
        $toolproxy->capabilityoffered = implode("\n", $config->mcodelti_capabilities);
    } else {
        $toolproxy->capabilityoffered = implode("\n", array_keys(mcodelti_get_capabilities()));
    }
    if (isset($config->mcodelti_services)) {
        $toolproxy->serviceoffered = implode("\n", $config->mcodelti_services);
    } else {
        $func = function($s) {
            return $s->get_id();
        };
        $servicenames = array_map($func, mcodelti_get_services());
        $toolproxy->serviceoffered = implode("\n", $servicenames);
    }
    if (isset($config->toolproxyid) && !empty($config->toolproxyid)) {
        $toolproxy->id = $config->toolproxyid;
        if (!isset($toolproxy->state) || ($toolproxy->state != MCODELTI_TOOL_PROXY_STATE_ACCEPTED)) {
            $toolproxy->state = MCODELTI_TOOL_PROXY_STATE_CONFIGURED;
            $toolproxy->guid = random_string();
            $toolproxy->secret = random_string();
        }
        $id = mcodelti_update_tool_proxy($toolproxy);
    } else {
        $toolproxy->state = MCODELTI_TOOL_PROXY_STATE_CONFIGURED;
        $toolproxy->timemodified = time();
        $toolproxy->timecreated = $toolproxy->timemodified;
        if (!isset($toolproxy->createdby)) {
            $toolproxy->createdby = $USER->id;
        }
        $toolproxy->guid = random_string();
        $toolproxy->secret = random_string();
        $id = $DB->insert_record('mcodelti_tool_proxies', $toolproxy);
    }

    return $id;
}

/**
 * Updates a tool proxy in the database
 *
 * @param object  $toolproxy   Tool proxy
 *
 * @return int    Record id number
 */
function mcodelti_update_tool_proxy($toolproxy) {
    global $DB;

    $toolproxy->timemodified = time();
    $id = $DB->update_record('mcodelti_tool_proxies', $toolproxy);

    return $id;
}

/**
 * Delete a Tool Proxy
 *
 * @param int $id   Tool Proxy id
 */
function mcodelti_delete_tool_proxy($id) {
    global $DB;
    $DB->delete_records('mcodelti_tool_settings', array('toolproxyid' => $id));
    $tools = $DB->get_records('mcodelti_types', array('toolproxyid' => $id));
    foreach ($tools as $tool) {
        mcodelti_delete_type($tool->id);
    }
    $DB->delete_records('mcodelti_tool_proxies', array('id' => $id));
}

/**
 * Add a tool configuration in the database
 *
 * @param object $config   Tool configuration
 *
 * @return int Record id number
 */
function mcodelti_add_config($config) {
    global $DB;

    return $DB->insert_record('mcodelti_types_config', $config);
}

/**
 * Updates a tool configuration in the database
 *
 * @param object  $config   Tool configuration
 *
 * @return Record id number
 */
function mcodelti_update_config($config) {
    global $DB;

    $return = true;
    $old = $DB->get_record('mcodelti_types_config', array('typeid' => $config->typeid, 'name' => $config->name));

    if ($old) {
        $config->id = $old->id;
        $return = $DB->update_record('mcodelti_types_config', $config);
    } else {
        $return = $DB->insert_record('mcodelti_types_config', $config);
    }
    return $return;
}

/**
 * Gets the tool settings
 *
 * @param int  $toolproxyid   Id of tool proxy record
 * @param int  $courseid      Id of course (null if system settings)
 * @param int  $instanceid    Id of course module (null if system or context settings)
 *
 * @return array  Array settings
 */
function mcodelti_get_tool_settings($toolproxyid, $courseid = null, $instanceid = null) {
    global $DB;

    $settings = array();
    $settingsstr = $DB->get_field('mcodelti_tool_settings', 'settings', array('toolproxyid' => $toolproxyid,
        'course' => $courseid, 'coursemoduleid' => $instanceid));
    if ($settingsstr !== false) {
        $settings = json_decode($settingsstr, true);
    }
    return $settings;
}

/**
 * Sets the tool settings (
 *
 * @param array  $settings      Array of settings
 * @param int    $toolproxyid   Id of tool proxy record
 * @param int    $courseid      Id of course (null if system settings)
 * @param int    $instanceid    Id of course module (null if system or context settings)
 */
function mcodelti_set_tool_settings($settings, $toolproxyid, $courseid = null, $instanceid = null) {
    global $DB;

    $json = json_encode($settings);
    $record = $DB->get_record('mcodelti_tool_settings', array('toolproxyid' => $toolproxyid,
        'course' => $courseid, 'coursemoduleid' => $instanceid));
    if ($record !== false) {
        $DB->update_record('mcodelti_tool_settings', array('id' => $record->id, 'settings' => $json, 'timemodified' => time()));
    } else {
        $record = new \stdClass();
        $record->toolproxyid = $toolproxyid;
        $record->course = $courseid;
        $record->coursemoduleid = $instanceid;
        $record->settings = $json;
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $DB->insert_record('mcodelti_tool_settings', $record);
    }
}

/**
 * Signs the petition to launch the external tool using OAuth
 *
 * @param $oldparms     Parameters to be passed for signing
 * @param $endpoint     url of the external tool
 * @param $method       Method for sending the parameters (e.g. POST)
 * @param $oauth_consumoer_key          Key
 * @param $oauth_consumoer_secret       Secret
 */
function mcodelti_sign_parameters($oldparms, $endpoint, $method, $oauthconsumerkey, $oauthconsumersecret) {

    $parms = $oldparms;



    $testtoken = '';

    // TODO: Switch to core oauthlib once implemented - MDL-30149.
    $hmacmethod = new mcodelti\OAuthSignatureMethod_HMAC_SHA1();

    $testconsumer = new mcodelti\OAuthConsumer($oauthconsumerkey, $oauthconsumersecret, null);
    $accreq = mcodelti\OAuthRequest::from_consumer_and_token($testconsumer, $testtoken, $method, $endpoint, $parms);
    $accreq->sign_request($hmacmethod, $testconsumer, $testtoken);


    $newparms = $accreq->get_parameters();


    return $newparms;
}

/**
 * Posts the launch petition HTML
 *
 * @param $newparms     Signed parameters
 * @param $endpoint     URL of the external tool
 * @param $debug        Debug (true/false)
 */
function mcodelti_post_launch_html($newparms, $endpoint, $debug=false) {
    $r = "<form action=\"" . $endpoint .
        "\" name=\"mcodeltiLaunchForm\" id=\"mcodeltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n";

    // Contruct html for the launch parameters.
    foreach ($newparms as $key => $value) {
        $key = htmlspecialchars($key);
        $value = htmlspecialchars($value);
        if ( $key == "ext_submit" ) {
            $r .= "<input type=\"submit\"";
        } else {
            $r .= "<input type=\"hidden\" name=\"{$key}\"";
        }
        $r .= " value=\"";
        $r .= $value;
        $r .= "\"/>\n";
    }

    if ( $debug ) {
        $r .= "<script language=\"javascript\"> \n";
        $r .= "  //<![CDATA[ \n";
        $r .= "function basicmcodeltiDebugToggle() {\n";
        $r .= "    var ele = document.getElementById(\"basicmcodeltiDebug\");\n";
        $r .= "    if (ele.style.display == \"block\") {\n";
        $r .= "        ele.style.display = \"none\";\n";
        $r .= "    }\n";
        $r .= "    else {\n";
        $r .= "        ele.style.display = \"block\";\n";
        $r .= "    }\n";
        $r .= "} \n";
        $r .= "  //]]> \n";
        $r .= "</script>\n";
        $r .= "<a id=\"displayText\" href=\"javascript:basicmcodeltiDebugToggle();\">";
        $r .= get_string("toggle_debug_data", "mcodelti")."</a>\n";
        $r .= "<div id=\"basicmcodeltiDebug\" style=\"display:none\">\n";
        $r .= "<b>".get_string("basicmcodelti_endpoint", "mcodelti")."</b><br/>\n";
        $r .= $endpoint . "<br/>\n&nbsp;<br/>\n";
        $r .= "<b>".get_string("basicmcodelti_parameters", "mcodelti")."</b><br/>\n";
        foreach ($newparms as $key => $value) {
            $key = htmlspecialchars($key);
            $value = htmlspecialchars($value);
            $r .= "$key = $value<br/>\n";
        }
        $r .= "&nbsp;<br/>\n";
        $r .= "</div>\n";
    }
    $r .= "</form>\n";

    if ( ! $debug ) {
        $r .= " <script type=\"text/javascript\"> \n" .
            "  //<![CDATA[ \n" .
            "    document.mcodeltiLaunchForm.submit(); \n" .
            "  //]]> \n" .
            " </script> \n";
    }
    return $r;
}

function mcodelti_get_type($typeid) {
    global $DB;

    return $DB->get_record('mcodelti_types', array('id' => $typeid));
}

function mcodelti_get_launch_container($mcodelti, $toolconfig) {
    if (empty($mcodelti->launchcontainer)) {
        $mcodelti->launchcontainer = MCODELTI_LAUNCH_CONTAINER_DEFAULT;
    }

    if ($mcodelti->launchcontainer == MCODELTI_LAUNCH_CONTAINER_DEFAULT) {
        if (isset($toolconfig['launchcontainer'])) {
            $launchcontainer = $toolconfig['launchcontainer'];
        }
    } else {
        $launchcontainer = $mcodelti->launchcontainer;
    }

    if (empty($launchcontainer) || $launchcontainer == MCODELTI_LAUNCH_CONTAINER_DEFAULT) {
        $launchcontainer = MCODELTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;
    }

    $devicetype = core_useragent::get_device_type();

    // Scrolling within the object element doesn't work on iOS or Android
    // Opening the popup window also had some issues in testing
    // For mobile devices, always take up the entire screen to ensure the best experience.
    if ($devicetype === core_useragent::DEVICETYPE_MOBILE || $devicetype === core_useragent::DEVICETYPE_TABLET ) {
        $launchcontainer = MCODELTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW;
    }

    return $launchcontainer;
}

function mcodelti_request_is_using_ssl() {
    global $CFG;
    return (stripos($CFG->wwwroot, 'https://') === 0);
}

function mcodelti_ensure_url_is_https($url) {
    if (!strstr($url, '://')) {
        $url = 'https://' . $url;
    } else {
        // If the URL starts with http, replace with https.
        if (stripos($url, 'http://') === 0) {
            $url = 'https://' . substr($url, 7);
        }
    }

    return $url;
}

/**
 * Determines if we should try to log the request
 *
 * @param string $rawbody
 * @return bool
 */
function mcodelti_should_log_request($rawbody) {
    global $CFG;

    if (empty($CFG->mod_mcodelti_log_users)) {
        return false;
    }

    $logusers = explode(',', $CFG->mod_mcodelti_log_users);
    if (empty($logusers)) {
        return false;
    }

    try {
        $xml = new \SimpleXMLElement($rawbody);
        $ns  = $xml->getNamespaces();
        $ns  = array_shift($ns);
        $xml->registerXPathNamespace('mcodelti', $ns);
        $requestuserid = '';
        if ($node = $xml->xpath('//mcodelti:userId')) {
            $node = $node[0];
            $requestuserid = clean_param((string) $node, PARAM_INT);
        } else if ($node = $xml->xpath('//mcodelti:sourcedId')) {
            $node = $node[0];
            $resultjson = json_decode((string) $node);
            $requestuserid = clean_param($resultjson->data->userid, PARAM_INT);
        }
    } catch (Exception $e) {
        return false;
    }

    if (empty($requestuserid) or !in_array($requestuserid, $logusers)) {
        return false;
    }

    return true;
}

/**
 * Logs the request to a file in temp dir.
 *
 * @param string $rawbody
 */
function mcodelti_log_request($rawbody) {
    if ($tempdir = make_temp_directory('mod_mcodelti', false)) {
        if ($tempfile = tempnam($tempdir, 'mod_mcodelti_request'.date('YmdHis'))) {
            $content  = "Request Headers:\n";
            foreach (moodle\mod\mcodelti\OAuthUtil::get_headers() as $header => $value) {
                $content .= "$header: $value\n";
            }
            $content .= "Request Body:\n";
            $content .= $rawbody;

            file_put_contents($tempfile, $content);
            chmod($tempfile, 0644);
        }
    }
}

/**
 * Log an mcodelti response.
 *
 * @param string $responsexml The response XML
 * @param Exception $e If there was an exception, pass that too
 */
function mcodelti_log_response($responsexml, $e = null) {
    if ($tempdir = make_temp_directory('mod_mcodelti', false)) {
        if ($tempfile = tempnam($tempdir, 'mod_mcodelti_response'.date('YmdHis'))) {
            $content = '';
            if ($e instanceof Exception) {
                $info = get_exception_info($e);

                $content .= "Exception:\n";
                $content .= "Message: $info->message\n";
                $content .= "Debug info: $info->debuginfo\n";
                $content .= "Backtrace:\n";
                $content .= format_backtrace($info->backtrace, true);
                $content .= "\n";
            }
            $content .= "Response XML:\n";
            $content .= $responsexml;

            file_put_contents($tempfile, $content);
            chmod($tempfile, 0644);
        }
    }
}

/**
 * Fetches mcodelti type configuration for an mcodelti instance
 *
 * @param stdClass $instance
 * @return array Can be empty if no type is found
 */
function mcodelti_get_type_config_by_instance($instance) {
    $typeid = null;
    if (empty($instance->typeid)) {
        $tool = mcodelti_get_tool_by_url_match($instance->toolurl, $instance->course);
        if ($tool) {
            $typeid = $tool->id;
        }
    } else {
        $typeid = $instance->typeid;
    }
    if (!empty($typeid)) {
        return mcodelti_get_type_config($typeid);
    }
    return array();
}

/**
 * Enforce type config settings onto the mcodelti instance
 *
 * @param stdClass $instance
 * @param array $typeconfig
 */
function mcodelti_force_type_config_settings($instance, array $typeconfig) {
    $forced = array(
        'instructorchoicesendname'      => 'sendname',
        'instructorchoicesendemailaddr' => 'sendemailaddr',
        'instructorchoiceacceptgrades'  => 'acceptgrades',
    );

    foreach ($forced as $instanceparam => $typeconfigparam) {
        if (array_key_exists($typeconfigparam, $typeconfig) && $typeconfig[$typeconfigparam] != MCODELTI_SETTING_DELEGATE) {
            $instance->$instanceparam = $typeconfig[$typeconfigparam];
        }
    }
}

/**
 * Initializes an array with the capabilities supported by the mcodelti module
 *
 * @return array List of capability names (without a dollar sign prefix)
 */
function mcodelti_get_capabilities() {

    $capabilities = array(
       'basic-mcodelti-launch-request' => '',
       'ContentItemSelectionRequest' => '',
       'ToolProxyRegistrationRequest' => '',
       'Context.id' => 'context_id',
       'Context.title' => 'context_title',
       'Context.label' => 'context_label',
       'Context.sourcedId' => 'lis_course_section_sourcedid',
       'Context.longDescription' => '$COURSE->summary',
       'Context.timeFrame.begin' => '$COURSE->startdate',
       'CourseSection.title' => 'context_title',
       'CourseSection.label' => 'context_label',
       'CourseSection.sourcedId' => 'lis_course_section_sourcedid',
       'CourseSection.longDescription' => '$COURSE->summary',
       'CourseSection.timeFrame.begin' => '$COURSE->startdate',
       'ResourceLink.id' => 'resource_link_id',
       'ResourceLink.title' => 'resource_link_title',
       'ResourceLink.description' => 'resource_link_description',
       'User.id' => 'user_id',
       'User.username' => '$USER->username',
       'Person.name.full' => 'lis_person_name_full',
       'Person.name.given' => 'lis_person_name_given',
       'Person.name.family' => 'lis_person_name_family',
       'Person.email.primary' => 'lis_person_contact_email_primary',
       'Person.sourcedId' => 'lis_person_sourcedid',
       'Person.name.middle' => '$USER->middlename',
       'Person.address.street1' => '$USER->address',
       'Person.address.locality' => '$USER->city',
       'Person.address.country' => '$USER->country',
       'Person.address.timezone' => '$USER->timezone',
       'Person.phone.primary' => '$USER->phone1',
       'Person.phone.mobile' => '$USER->phone2',
       'Person.webaddress' => '$USER->url',
       'Membership.role' => 'roles',
       'Result.sourcedId' => 'lis_result_sourcedid',
       'Result.autocreate' => 'lis_outcome_service_url',
       'Moodle.Person.userGroupIds' => null);

    return $capabilities;

}

/**
 * Initializes an array with the services supported by the mcodelti module
 *
 * @return array List of services
 */
function mcodelti_get_services() {

    $services = array();
    $definedservices = core_component::get_plugin_list('mcodeltiservice');
    foreach ($definedservices as $name => $location) {
        $classname = "\\mcodeltiservice_{$name}\\local\\service\\{$name}";
        $services[] = new $classname();
    }

    return $services;

}

/**
 * Initializes an instance of the named service
 *
 * @param string $servicename Name of service
 *
 * @return mod_mcodelti\local\mcodeltiservice\service_base Service
 */
function mcodelti_get_service_by_name($servicename) {

    $service = false;
    $classname = "\\mcodeltiservice_{$servicename}\\local\\service\\{$servicename}";
    if (class_exists($classname)) {
        $service = new $classname();
    }

    return $service;

}

/**
 * Finds a service by id
 *
 * @param array  $services    Array of services
 * @param string $resourceid  ID of resource
 *
 * @return mod_mcodelti\local\mcodeltiservice\service_base Service
 */
function mcodelti_get_service_by_resource_id($services, $resourceid) {

    $service = false;
    foreach ($services as $aservice) {
        foreach ($aservice->get_resources() as $resource) {
            if ($resource->get_id() === $resourceid) {
                $service = $aservice;
                break 2;
            }
        }
    }

    return $service;

}

/**
 * Extracts the named contexts from a tool proxy
 *
 * @param object $json
 *
 * @return array Contexts
 */
function mcodelti_get_contexts($json) {

    $contexts = array();
    if (isset($json->{'@context'})) {
        foreach ($json->{'@context'} as $context) {
            if (is_object($context)) {
                $contexts = array_merge(get_object_vars($context), $contexts);
            }
        }
    }

    return $contexts;

}

/**
 * Converts an ID to a fully-qualified ID
 *
 * @param array $contexts
 * @param string $id
 *
 * @return string Fully-qualified ID
 */
function mcodelti_get_fqid($contexts, $id) {

    $parts = explode(':', $id, 2);
    if (count($parts) > 1) {
        if (array_key_exists($parts[0], $contexts)) {
            $id = $contexts[$parts[0]] . $parts[1];
        }
    }

    return $id;

}

/**
 * Returns the icon for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string The url to the tool type's corresponding icon
 */
function get_tool_type_icon_url_mcode(stdClass $type) {
    global $OUTPUT;

    $iconurl = $type->secureicon;

    if (empty($iconurl)) {
        $iconurl = $type->icon;
    }

    if (empty($iconurl)) {
        $iconurl = $OUTPUT->image_url('icon', 'mcodelti')->out();
    }

    return $iconurl;
}

/**
 * Returns the edit url for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string The url to edit the tool type
 */
function get_tool_type_edit_url_mcode(stdClass $type) {
    $url = new moodle_url('/mod/mcodelti/typessettings.php',
                          array('action' => 'update', 'id' => $type->id, 'sesskey' => sesskey(), 'returnto' => 'toolconfigure'));
    return $url->out();
}

/**
 * Returns the edit url for the given tool proxy.
 *
 * @param stdClass $proxy The tool proxy
 *
 * @return string The url to edit the tool type
 */
function get_tool_proxy_edit_url_mcode_mcode_mcode_mcode(stdClass $proxy) {
    $url = new moodle_url('/mod/mcodelti/registersettings.php',
                          array('action' => 'update', 'id' => $proxy->id, 'sesskey' => sesskey(), 'returnto' => 'toolconfigure'));
    return $url->out();
}

/**
 * Returns the course url for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string|void The url to the course of the tool type, void if it is a site wide type
 */
function get_tool_type_course_url_mcode(stdClass $type) {
    if ($type->course == 1) {
        return;
    } else {
        $url = new moodle_url('/course/view.php', array('id' => $type->course));
        return $url->out();
    }
}

/**
 * Returns the icon and edit urls for the tool type and the course url if it is a course type.
 *
 * @param stdClass $type The tool type
 *
 * @return string The urls of the tool type
 */
function get_tool_type_urls_mcode(stdClass $type) {
    $courseurl = get_tool_type_course_url_mcode($type);

    $urls = array(
        'icon' => get_tool_type_icon_url_mcode($type),
        'edit' => get_tool_type_edit_url_mcode($type),
    );

    if ($courseurl) {
        $urls['course'] = $courseurl;
    }

    return $urls;
}

/**
 * Returns the icon and edit urls for the tool proxy.
 *
 * @param stdClass $proxy The tool proxy
 *
 * @return string The urls of the tool proxy
 */
function get_tool_proxy_urls_mcode(stdClass $proxy) {
    global $OUTPUT;

    $urls = array(
        'icon' => $OUTPUT->image_url('icon', 'mcodelti')->out(),
        'edit' => get_tool_proxy_edit_url_mcode_mcode_mcode_mcode($proxy),
    );

    return $urls;
}

/**
 * Returns information on the current state of the tool type
 *
 * @param stdClass $type The tool type
 *
 * @return array An array with a text description of the state, and boolean for whether it is in each state:
 * pending, configured, rejected, unknown
 */
function get_tool_type_state_info_mcode(stdClass $type) {
    $state = '';
    $isconfigured = false;
    $ispending = false;
    $isrejected = false;
    $isunknown = false;
    switch ($type->state) {
        case MCODELTI_TOOL_STATE_CONFIGURED:
            $state = get_string('active', 'mod_mcodelti');
            $isconfigured = true;
            break;
        case MCODELTI_TOOL_STATE_PENDING:
            $state = get_string('pending', 'mod_mcodelti');
            $ispending = true;
            break;
        case MCODELTI_TOOL_STATE_REJECTED:
            $state = get_string('rejected', 'mod_mcodelti');
            $isrejected = true;
            break;
        default:
            $state = get_string('unknownstate', 'mod_mcodelti');
            $isunknown = true;
            break;
    }

    return array(
        'text' => $state,
        'pending' => $ispending,
        'configured' => $isconfigured,
        'rejected' => $isrejected,
        'unknown' => $isunknown
    );
}

/**
 * Returns a summary of each mcodelti capability this tool type requires in plain language
 *
 * @param stdClass $type The tool type
 *
 * @return array An array of text descriptions of each of the capabilities this tool type requires
 */
function get_tool_type_capability_groups_mcode($type) {
    $capabilities = mcodelti_get_enabled_capabilities($type);
    $groups = array();
    $hascourse = false;
    $hasactivities = false;
    $hasuseraccount = false;
    $hasuserpersonal = false;

    foreach ($capabilities as $capability) {
        // Bail out early if we've already found all groups.
        if (count($groups) >= 4) {
            continue;
        }

        if (!$hascourse && preg_match('/^CourseSection/', $capability)) {
            $hascourse = true;
            $groups[] = get_string('courseinformation', 'mod_mcodelti');
        } else if (!$hasactivities && preg_match('/^ResourceLink/', $capability)) {
            $hasactivities = true;
            $groups[] = get_string('courseactivitiesorresources', 'mod_mcodelti');
        } else if (!$hasuseraccount && preg_match('/^User/', $capability) || preg_match('/^Membership/', $capability)) {
            $hasuseraccount = true;
            $groups[] = get_string('useraccountinformation', 'mod_mcodelti');
        } else if (!$hasuserpersonal && preg_match('/^Person/', $capability)) {
            $hasuserpersonal = true;
            $groups[] = get_string('userpersonalinformation', 'mod_mcodelti');
        }
    }

    return $groups;
}


/**
 * Returns the ids of each instance of this tool type
 *
 * @param stdClass $type The tool type
 *
 * @return array An array of ids of the instances of this tool type
 */
function get_tool_type_instance_ids_mcode($type) {
    global $DB;

    return array_keys($DB->get_fieldset_select('mcodelti', 'id', 'typeid = ?', array($type->id)));
}

/**
 * Serialises this tool type
 *
 * @param stdClass $type The tool type
 *
 * @return array An array of values representing this type
 */
function serialise_tool_type_mcode(stdClass $type) {
    $capabilitygroups = get_tool_type_capability_groups_mcode($type);
    $instanceids = get_tool_type_instance_ids_mcode($type);
    // Clean the name. We don't want tags here.
    $name = clean_param($type->name, PARAM_NOTAGS);
    if (!empty($type->description)) {
        // Clean the description. We don't want tags here.
        $description = clean_param($type->description, PARAM_NOTAGS);
    } else {
        $description = get_string('editdescription', 'mod_mcodelti');
    }
    return array(
        'id' => $type->id,
        'name' => $name,
        'description' => $description,
        'urls' => get_tool_type_urls_mcode($type),
        'state' => get_tool_type_state_info_mcode($type),
        'hascapabilitygroups' => !empty($capabilitygroups),
        'capabilitygroups' => $capabilitygroups,
        // Course ID of 1 means it's not linked to a course.
        'courseid' => $type->course == 1 ? 0 : $type->course,
        'instanceids' => $instanceids,
        'instancecount' => count($instanceids)
    );
}

/**
 * Serialises this tool proxy.
 *
 * @param stdClass $proxy The tool proxy
 *
 * @return array An array of values representing this type
 */
function serialise_tool_proxy_mcode(stdClass $proxy) {
    return array(
        'id' => $proxy->id,
        'name' => $proxy->name,
        'description' => get_string('activatetoadddescription', 'mod_mcodelti'),
        'urls' => get_tool_proxy_urls_mcode($proxy),
        'state' => array(
            'text' => get_string('pending', 'mod_mcodelti'),
            'pending' => true,
            'configured' => false,
            'rejected' => false,
            'unknown' => false
        ),
        'hascapabilitygroups' => true,
        'capabilitygroups' => array(),
        'courseid' => 0,
        'instanceids' => array(),
        'instancecount' => 0
    );
}

/**
 * Loads the cartridge information into the tool type, if the launch url is for a cartridge file
 *
 * @param stdClass $type The tool type object to be filled in
 * @since Moodle 3.1
 */
function mcodelti_load_type_if_cartridge($type) {
    if (!empty($type->mcodelti_toolurl) && mcodelti_is_cartridge($type->mcodelti_toolurl)) {
        mcodelti_load_type_from_cartridge($type->mcodelti_toolurl, $type);
    }
}

/**
 * Loads the cartridge information into the new tool, if the launch url is for a cartridge file
 *
 * @param stdClass $mcodelti The tools config
 * @since Moodle 3.1
 */
function mcodelti_load_tool_if_cartridge($mcodelti) {
    if (!empty($mcodelti->toolurl) && mcodelti_is_cartridge($mcodelti->toolurl)) {
        mcodelti_load_tool_from_cartridge($mcodelti->toolurl, $mcodelti);
    }
}

/**
 * Determines if the given url is for a IMS basic cartridge
 *
 * @param  string $url The url to be checked
 * @return True if the url is for a cartridge
 * @since Moodle 3.1
 */
function mcodelti_is_cartridge($url) {
    // If it is empty, it's not a cartridge.
    if (empty($url)) {
        return false;
    }
    // If it has xml at the end of the url, it's a cartridge.
    if (preg_match('/\.xml$/', $url)) {
        return true;
    }
    // Even if it doesn't have .xml, load the url to check if it's a cartridge..
    try {
        $toolinfo = mcodelti_load_cartridge($url,
            array(
                "launch_url" => "launchurl"
            )
        );
        if (!empty($toolinfo['launchurl'])) {
            return true;
        }
    } catch (moodle_exception $e) {
        return false; // Error loading the xml, so it's not a cartridge.
    }
    return false;
}

/**
 * Allows you to load settings for an external tool type from an IMS cartridge.
 *
 * @param  string   $url     The URL to the cartridge
 * @param  stdClass $type    The tool type object to be filled in
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function mcodelti_load_type_from_cartridge($url, $type) {
    $toolinfo = mcodelti_load_cartridge($url,
        array(
            "title" => "mcodelti_typename",
            "launch_url" => "mcodelti_toolurl",
            "description" => "mcodelti_description",
            "icon" => "mcodelti_icon",
            "secure_icon" => "mcodelti_secureicon"
        ),
        array(
            "icon_url" => "mcodelti_extension_icon",
            "secure_icon_url" => "mcodelti_extension_secureicon"
        )
    );
    // If an activity name exists, unset the cartridge name so we don't override it.
    if (isset($type->mcodelti_typename)) {
        unset($toolinfo['mcodelti_typename']);
    }

    // Always prefer cartridge core icons first, then, if none are found, look at the extension icons.
    if (empty($toolinfo['mcodelti_icon']) && !empty($toolinfo['mcodelti_extension_icon'])) {
        $toolinfo['mcodelti_icon'] = $toolinfo['mcodelti_extension_icon'];
    }
    unset($toolinfo['mcodelti_extension_icon']);

    if (empty($toolinfo['mcodelti_secureicon']) && !empty($toolinfo['mcodelti_extension_secureicon'])) {
        $toolinfo['mcodelti_secureicon'] = $toolinfo['mcodelti_extension_secureicon'];
    }
    unset($toolinfo['mcodelti_extension_secureicon']);

    foreach ($toolinfo as $property => $value) {
        $type->$property = $value;
    }
}

/**
 * Allows you to load in the configuration for an external tool from an IMS cartridge.
 *
 * @param  string   $url    The URL to the cartridge
 * @param  stdClass $mcodelti    mcodelti object
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function mcodelti_load_tool_from_cartridge($url, $mcodelti) {
    $toolinfo = mcodelti_load_cartridge($url,
        array(
            "title" => "name",
            "launch_url" => "toolurl",
            "secure_launch_url" => "securetoolurl",
            "description" => "intro",
            "icon" => "icon",
            "secure_icon" => "secureicon"
        ),
        array(
            "icon_url" => "extension_icon",
            "secure_icon_url" => "extension_secureicon"
        )
    );
    // If an activity name exists, unset the cartridge name so we don't override it.
    if (isset($mcodelti->name)) {
        unset($toolinfo['name']);
    }

    // Always prefer cartridge core icons first, then, if none are found, look at the extension icons.
    if (empty($toolinfo['icon']) && !empty($toolinfo['extension_icon'])) {
        $toolinfo['icon'] = $toolinfo['extension_icon'];
    }
    unset($toolinfo['extension_icon']);

    if (empty($toolinfo['secureicon']) && !empty($toolinfo['extension_secureicon'])) {
        $toolinfo['secureicon'] = $toolinfo['extension_secureicon'];
    }
    unset($toolinfo['extension_secureicon']);

    foreach ($toolinfo as $property => $value) {
        $mcodelti->$property = $value;
    }
}

/**
 * Search for a tag within an XML DOMDocument
 *
 * @param  string $url The url of the cartridge to be loaded
 * @param  array  $map The map of tags to keys in the return array
 * @param  array  $propertiesmap The map of properties to keys in the return array
 * @return array An associative array with the given keys and their values from the cartridge
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function mcodelti_load_cartridge($url, $map, $propertiesmap = array()) {
    global $CFG;
    require_once($CFG->libdir. "/filelib.php");

    $curl = new curl();
    $response = $curl->get($url);

    // TODO MDL-46023 Replace this code with a call to the new library.
    $origerrors = libxml_use_internal_errors(true);
    $origentity = libxml_disable_entity_loader(true);
    libxml_clear_errors();

    $document = new DOMDocument();
    @$document->loadXML($response, LIBXML_DTDLOAD | LIBXML_DTDATTR);

    $cartridge = new DomXpath($document);

    $errors = libxml_get_errors();

    libxml_clear_errors();
    libxml_use_internal_errors($origerrors);
    libxml_disable_entity_loader($origentity);

    if (count($errors) > 0) {
        $message = 'Failed to load cartridge.';
        foreach ($errors as $error) {
            $message .= "\n" . trim($error->message, "\n\r\t .") . " at line " . $error->line;
        }
        throw new moodle_exception('errorreadingfile', '', '', $url, $message);
    }

    $toolinfo = array();
    foreach ($map as $tag => $key) {
        $value = get_tag_mcode($tag, $cartridge);
        if ($value) {
            $toolinfo[$key] = $value;
        }
    }
    if (!empty($propertiesmap)) {
        foreach ($propertiesmap as $property => $key) {
            $value = get_tag_mcode("property", $cartridge, $property);
            if ($value) {
                $toolinfo[$key] = $value;
            }
        }
    }

    return $toolinfo;
}

/**
 * Search for a tag within an XML DOMDocument
 *
 * @param  stdClass $tagname The name of the tag to search for
 * @param  XPath    $xpath   The XML to find the tag in
 * @param  XPath    $attribute The attribute to search for (if we should search for a child node with the given
 * value for the name attribute
 * @since Moodle 3.1
 */
function get_tag_mcode($tagname, $xpath, $attribute = null) {
    if ($attribute) {
        $result = $xpath->query('//*[local-name() = \'' . $tagname . '\'][@name="' . $attribute . '"]');
    } else {
        $result = $xpath->query('//*[local-name() = \'' . $tagname . '\']');
    }
    if ($result->length > 0) {
        return $result->item(0)->nodeValue;
    }
    return null;
}
