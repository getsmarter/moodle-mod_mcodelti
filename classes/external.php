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
 * External tool module external API
 *
 * @package    mod_mcodelti
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/mcodelti/lib.php');
require_once($CFG->dirroot . '/mod/mcodelti/locallib.php');

/**
 * External tool module external functions
 *
 * @package    mod_mcodelti
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_mcodelti_external extends external_api {

    /**
     * Returns structure be used for returning a tool type from a web service.
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    private static function tool_type_return_structure() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
                'name' => new external_value(PARAM_NOTAGS, 'Tool type name'),
                'description' => new external_value(PARAM_NOTAGS, 'Tool type description'),
                'urls' => new external_single_structure(
                    array(
                        'icon' => new external_value(PARAM_URL, 'Tool type icon URL'),
                        'edit' => new external_value(PARAM_URL, 'Tool type edit URL'),
                        'course' => new external_value(PARAM_URL, 'Tool type edit URL', VALUE_OPTIONAL),
                    )
                ),
                'state' => new external_single_structure(
                    array(
                        'text' => new external_value(PARAM_TEXT, 'Tool type state name string'),
                        'pending' => new external_value(PARAM_BOOL, 'Is the state pending'),
                        'configured' => new external_value(PARAM_BOOL, 'Is the state configured'),
                        'rejected' => new external_value(PARAM_BOOL, 'Is the state rejected'),
                        'unknown' => new external_value(PARAM_BOOL, 'Is the state unknown'),
                    )
                ),
                'hascapabilitygroups' => new external_value(PARAM_BOOL, 'Indicate if capabilitygroups is populated'),
                'capabilitygroups' => new external_mumcodeltiple_structure(
                    new external_value(PARAM_TEXT, 'Tool type capability groups enabled'),
                    'Array of capability groups', VALUE_DEFAULT, array()
                ),
                'courseid' => new external_value(PARAM_INT, 'Tool type course', VALUE_DEFAULT, 0),
                'instanceids' => new external_mumcodeltiple_structure(
                    new external_value(PARAM_INT, 'mcodelti instance ID'),
                    'IDs for the mcodelti instances using this type', VALUE_DEFAULT, array()
                ),
                'instancecount' => new external_value(PARAM_INT, 'The number of times this tool is being used')
            ), 'Tool'
        );
    }

    /**
     * Returns description of a tool proxy
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    private static function tool_proxy_return_structure() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool proxy id'),
                'name' => new external_value(PARAM_TEXT, 'Tool proxy name'),
                'regurl' => new external_value(PARAM_URL, 'Tool proxy registration URL'),
                'state' => new external_value(PARAM_INT, 'Tool proxy state'),
                'guid' => new external_value(PARAM_TEXT, 'Tool proxy globally unique identifier'),
                'secret' => new external_value(PARAM_TEXT, 'Tool proxy shared secret'),
                'vendorcode' => new external_value(PARAM_TEXT, 'Tool proxy consumer code'),
                'capabilityoffered' => new external_value(PARAM_TEXT, 'Tool proxy capabilities offered'),
                'serviceoffered' => new external_value(PARAM_TEXT, 'Tool proxy services offered'),
                'toolproxy' => new external_value(PARAM_TEXT, 'Tool proxy'),
                'timecreated' => new external_value(PARAM_INT, 'Tool proxy time created'),
                'timemodified' => new external_value(PARAM_INT, 'Tool proxy modified'),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_tool_proxies_parameters() {
        return new external_function_parameters(
            array(
                'orphanedonly' => new external_value(PARAM_BOOL, 'Orphaned tool types only', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Returns the tool types.
     *
     * @param bool $orphanedonly Retrieve only tool proxies that do not have a corresponding tool type
     * @return array of tool types
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_tool_proxies($orphanedonly) {
        global $PAGE;
        $params = self::validate_parameters(self::get_tool_proxies_parameters(),
                                            array(
                                                'orphanedonly' => $orphanedonly
                                            ));
        $orphanedonly = $params['orphanedonly'];

        $proxies = array();
        $context = context_system::instance();

        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $proxies = mcodelti_get_tool_proxies($orphanedonly);

        return array_map('serialise_tool_proxy_mcode', $proxies);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_tool_proxies_returns() {
        return new external_mumcodeltiple_structure(
            self::tool_type_return_structure()
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_tool_launch_data_parameters() {
        return new external_function_parameters(
            array(
                'toolid' => new external_value(PARAM_INT, 'external tool instance id')
            )
        );
    }

    /**
     * Return the launch data for a given external tool.
     *
     * @param int $toolid the external tool instance id
     * @return array of warnings and launch data
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function get_tool_launch_data($toolid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/mcodelti/lib.php');

        $params = self::validate_parameters(self::get_tool_launch_data_parameters(),
                                            array(
                                                'toolid' => $toolid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $mcodelti = $DB->get_record('mcodelti', array('id' => $params['toolid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($mcodelti, 'mcodelti');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/mcodelti:view', $context);

        $mcodelti->cmid = $cm->id;
        list($endpoint, $parms) = mcodelti_get_launch_data($mcodelti);

        $parameters = array();
        foreach ($parms as $name => $value) {
            $parameters[] = array(
                'name' => $name,
                'value' => $value
            );
        }

        $result = array();
        $result['endpoint'] = $endpoint;
        $result['parameters'] = $parameters;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function get_tool_launch_data_returns() {
        return new external_single_structure(
            array(
                'endpoint' => new external_value(PARAM_RAW, 'Endpoint URL'), // Using PARAM_RAW as is defined in the module.
                'parameters' => new external_mumcodeltiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_NOTAGS, 'Parameter name'),
                            'value' => new external_value(PARAM_RAW, 'Parameter value')
                        )
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_mcodeltis_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_mcodeltis_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_mumcodeltiple_structure(
                    new external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of external tools in a provided list of courses,
     * if no list is provided all external tools that the user can view will be returned.
     *
     * @param array $courseids the course ids
     * @return array the mcodelti details
     * @since Moodle 3.0
     */
    public static function get_mcodeltis_by_courses($courseids = array()) {
        global $CFG;

        $returnedmcodeltis = array();
        $warnings = array();

        $params = self::validate_parameters(self::get_mcodeltis_by_courses_parameters(), array('courseids' => $courseids));

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the mcodeltis in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $mcodeltis = get_all_instances_in_courses("mcodelti", $courses);

            foreach ($mcodeltis as $mcodelti) {

                $context = context_module::instance($mcodelti->coursemodule);

                // Entry to return.
                $module = array();

                // First, we return information that any user can see in (or can deduce from) the web interface.
                $module['id'] = $mcodelti->id;
                $module['coursemodule'] = $mcodelti->coursemodule;
                $module['course'] = $mcodelti->course;
                $module['name']  = external_format_string($mcodelti->name, $context->id);

                $viewablefields = [];
                if (has_capability('mod/mcodelti:view', $context)) {
                    list($module['intro'], $module['introformat']) =
                        external_format_text($mcodelti->intro, $mcodelti->introformat, $context->id, 'mod_mcodelti', 'intro', null);

                    $module['introfiles'] = external_util::get_area_files($context->id, 'mod_mcodelti', 'intro', false, false);
                    $viewablefields = array('launchcontainer', 'showtitlelaunch', 'showdescriptionlaunch', 'icon', 'secureicon');
                }

                // Check additional permissions for returning optional private settings.
                if (has_capability('moodle/course:manageactivities', $context)) {

                    $additionalfields = array('timecreated', 'timemodified', 'typeid', 'toolurl', 'securetoolurl',
                        'instructorchoicesendname', 'instructorchoicesendemailaddr', 'instructorchoiceallowroster',
                        'instructorchoiceallowsetting', 'instructorcustomparameters', 'instructorchoiceacceptgrades', 'grade',
                        'resourcekey', 'password', 'debuglaunch', 'servicesalt', 'visible', 'groupmode', 'groupingid');
                    $viewablefields = array_merge($viewablefields, $additionalfields);

                }

                foreach ($viewablefields as $field) {
                    $module[$field] = $mcodelti->{$field};
                }

                $returnedmcodeltis[] = $module;
            }
        }

        $result = array();
        $result['mcodeltis'] = $returnedmcodeltis;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_mcodeltis_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function get_mcodeltis_by_courses_returns() {

        return new external_single_structure(
            array(
                'mcodeltis' => new external_mumcodeltiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'External tool id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'mcodelti name'),
                            'intro' => new external_value(PARAM_RAW, 'The mcodelti intro', VALUE_OPTIONAL),
                            'introformat' => new external_format_value('intro', VALUE_OPTIONAL),
                            'introfiles' => new external_files('Files in the introduction text', VALUE_OPTIONAL),
                            'timecreated' => new external_value(PARAM_INT, 'Time of creation', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT, 'Time of last modification', VALUE_OPTIONAL),
                            'typeid' => new external_value(PARAM_INT, 'Type id', VALUE_OPTIONAL),
                            'toolurl' => new external_value(PARAM_URL, 'Tool url', VALUE_OPTIONAL),
                            'securetoolurl' => new external_value(PARAM_RAW, 'Secure tool url', VALUE_OPTIONAL),
                            'instructorchoicesendname' => new external_value(PARAM_TEXT, 'Instructor choice send name',
                                                                               VALUE_OPTIONAL),
                            'instructorchoicesendemailaddr' => new external_value(PARAM_INT, 'instructor choice send mail address',
                                                                                    VALUE_OPTIONAL),
                            'instructorchoiceallowroster' => new external_value(PARAM_INT, 'Instructor choice allow roster',
                                                                                VALUE_OPTIONAL),
                            'instructorchoiceallowsetting' => new external_value(PARAM_INT, 'Instructor choice allow setting',
                                                                                 VALUE_OPTIONAL),
                            'instructorcustomparameters' => new external_value(PARAM_RAW, 'instructor custom parameters',
                                                                                VALUE_OPTIONAL),
                            'instructorchoiceacceptgrades' => new external_value(PARAM_INT, 'instructor choice accept grades',
                                                                                    VALUE_OPTIONAL),
                            'grade' => new external_value(PARAM_INT, 'Enable grades', VALUE_OPTIONAL),
                            'launchcontainer' => new external_value(PARAM_INT, 'Launch container mode', VALUE_OPTIONAL),
                            'resourcekey' => new external_value(PARAM_RAW, 'Resource key', VALUE_OPTIONAL),
                            'password' => new external_value(PARAM_RAW, 'Shared secret', VALUE_OPTIONAL),
                            'debuglaunch' => new external_value(PARAM_INT, 'Debug launch', VALUE_OPTIONAL),
                            'showtitlelaunch' => new external_value(PARAM_INT, 'Show title launch', VALUE_OPTIONAL),
                            'showdescriptionlaunch' => new external_value(PARAM_INT, 'Show description launch', VALUE_OPTIONAL),
                            'servicesalt' => new external_value(PARAM_RAW, 'Service salt', VALUE_OPTIONAL),
                            'icon' => new external_value(PARAM_URL, 'Alternative icon URL', VALUE_OPTIONAL),
                            'secureicon' => new external_value(PARAM_URL, 'Secure icon URL', VALUE_OPTIONAL),
                            'section' => new external_value(PARAM_INT, 'course section id', VALUE_OPTIONAL),
                            'visible' => new external_value(PARAM_INT, 'visible', VALUE_OPTIONAL),
                            'groupmode' => new external_value(PARAM_INT, 'group mode', VALUE_OPTIONAL),
                            'groupingid' => new external_value(PARAM_INT, 'group id', VALUE_OPTIONAL),
                        ), 'Tool'
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_mcodelti_parameters() {
        return new external_function_parameters(
            array(
                'mcodeltiid' => new external_value(PARAM_INT, 'mcodelti instance id')
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $mcodeltiid the mcodelti instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_mcodelti($mcodeltiid) {
        global $DB;

        $params = self::validate_parameters(self::view_mcodelti_parameters(),
                                            array(
                                                'mcodeltiid' => $mcodeltiid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $mcodelti = $DB->get_record('mcodelti', array('id' => $params['mcodeltiid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($mcodelti, 'mcodelti');

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/mcodelti:view', $context);

        // Trigger course_module_viewed event and completion.
        mcodelti_view($mcodelti, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function view_mcodelti_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function create_tool_proxy_parameters() {
        return new external_function_parameters(
            array(
                'name' => new external_value(PARAM_TEXT, 'Tool proxy name', VALUE_DEFAULT, ''),
                'regurl' => new external_value(PARAM_URL, 'Tool proxy registration URL'),
                'capabilityoffered' => new external_mumcodeltiple_structure(
                    new external_value(PARAM_TEXT, 'Tool proxy capabilities offered'),
                    'Array of capabilities', VALUE_DEFAULT, array()
                ),
                'serviceoffered' => new external_mumcodeltiple_structure(
                    new external_value(PARAM_TEXT, 'Tool proxy services offered'),
                    'Array of services', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Creates a new tool proxy
     *
     * @param string $name Tool proxy name
     * @param string $registrationurl Registration url
     * @param string[] $capabilityoffered List of capabilities this tool proxy should be offered
     * @param string[] $serviceoffered List of services this tool proxy should be offered
     * @return object The new tool proxy
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function create_tool_proxy($name, $registrationurl, $capabilityoffered, $serviceoffered) {
        $params = self::validate_parameters(self::create_tool_proxy_parameters(),
                                            array(
                                                'name' => $name,
                                                'regurl' => $registrationurl,
                                                'capabilityoffered' => $capabilityoffered,
                                                'serviceoffered' => $serviceoffered
                                            ));
        $name = $params['name'];
        $regurl = $params['regurl'];
        $capabilityoffered = $params['capabilityoffered'];
        $serviceoffered = $params['serviceoffered'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Can't create duplicate proxies with the same URL.
        $duplicates = mcodelti_get_tool_proxies_from_registration_url($registrationurl);
        if (!empty($duplicates)) {
            throw new moodle_exception('duplicateregurl', 'mod_mcodelti');
        }

        $config = new stdClass();
        $config->mcodelti_registrationurl = $registrationurl;

        if (!empty($name)) {
            $config->mcodelti_registrationname = $name;
        }

        if (!empty($capabilityoffered)) {
            $config->mcodelti_capabilities = $capabilityoffered;
        }

        if (!empty($serviceoffered)) {
            $config->mcodelti_services = $serviceoffered;
        }

        $id = mcodelti_add_tool_proxy($config);
        $toolproxy = mcodelti_get_tool_proxy($id);

        // Pending makes more sense than configured as the first state, since
        // the next step is to register, which requires the state be pending.
        $toolproxy->state = mcodelti_TOOL_PROXY_STATE_PENDING;
        mcodelti_update_tool_proxy($toolproxy);

        return $toolproxy;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function create_tool_proxy_returns() {
        return self::tool_proxy_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function delete_tool_proxy_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool proxy id'),
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $id the mcodelti instance id
     * @return object The tool proxy
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function delete_tool_proxy($id) {
        $params = self::validate_parameters(self::delete_tool_proxy_parameters(),
                                            array(
                                                'id' => $id,
                                            ));
        $id = $params['id'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $toolproxy = mcodelti_get_tool_proxy($id);

        mcodelti_delete_tool_proxy($id);

        return $toolproxy;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function delete_tool_proxy_returns() {
        return self::tool_proxy_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_tool_proxy_registration_request_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool proxy id'),
            )
        );
    }

    /**
     * Returns the registration request for a tool proxy.
     *
     * @param int $id the mcodelti instance id
     * @return array of registration parameters
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_tool_proxy_registration_request($id) {
        $params = self::validate_parameters(self::get_tool_proxy_registration_request_parameters(),
                                            array(
                                                'id' => $id,
                                            ));
        $id = $params['id'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $toolproxy = mcodelti_get_tool_proxy($id);
        return mcodelti_build_registration_request($toolproxy);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_tool_proxy_registration_request_returns() {
        return new external_function_parameters(
            array(
                'mcodelti_message_type' => new external_value(PARAM_ALPHANUMEXT, 'mcodelti message type'),
                'mcodelti_version' => new external_value(PARAM_ALPHANUMEXT, 'mcodelti version'),
                'reg_key' => new external_value(PARAM_TEXT, 'Tool proxy registration key'),
                'reg_password' => new external_value(PARAM_TEXT, 'Tool proxy registration password'),
                'reg_url' => new external_value(PARAM_TEXT, 'Tool proxy registration url'),
                'tc_profile_url' => new external_value(PARAM_URL, 'Tool consumers profile URL'),
                'launch_presentation_return_url' => new external_value(PARAM_URL, 'URL to redirect on registration completion'),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_tool_types_parameters() {
        return new external_function_parameters(
            array(
                'toolproxyid' => new external_value(PARAM_INT, 'Tool proxy id', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Returns the tool types.
     *
     * @param int $toolproxyid The tool proxy id
     * @return array of tool types
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_tool_types($toolproxyid) {
        global $PAGE;
        $params = self::validate_parameters(self::get_tool_types_parameters(),
                                            array(
                                                'toolproxyid' => $toolproxyid
                                            ));
        $toolproxyid = $params['toolproxyid'];

        $types = array();
        $context = context_system::instance();

        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        if (!empty($toolproxyid)) {
            $types = mcodelti_get_mcodelti_types_from_proxy_id($toolproxyid);
        } else {
            $types = mcodelti_get_mcodelti_types();
        }

        return array_map("serialise_tool_type_mcode", array_values($types));
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_tool_types_returns() {
        return new external_mumcodeltiple_structure(
            self::tool_type_return_structure()
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function create_tool_type_parameters() {
        return new external_function_parameters(
            array(
                'cartridgeurl' => new external_value(PARAM_URL, 'URL to cardridge to load tool information', VALUE_DEFAULT, ''),
                'key' => new external_value(PARAM_TEXT, 'Consumer key', VALUE_DEFAULT, ''),
                'secret' => new external_value(PARAM_TEXT, 'Shared secret', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Creates a tool type.
     *
     * @param string $cartridgeurl Url of the xml cartridge representing the mcodelti tool
     * @param string $key The consumer key to identify this consumer
     * @param string $secret The secret
     * @return array created tool type
     * @since Moodle 3.1
     * @throws moodle_exception If the tool type could not be created
     */
    public static function create_tool_type($cartridgeurl, $key, $secret) {
        $params = self::validate_parameters(self::create_tool_type_parameters(),
                                            array(
                                                'cartridgeurl' => $cartridgeurl,
                                                'key' => $key,
                                                'secret' => $secret
                                            ));
        $cartridgeurl = $params['cartridgeurl'];
        $key = $params['key'];
        $secret = $params['secret'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $id = null;

        if (!empty($cartridgeurl)) {
            $type = new stdClass();
            $data = new stdClass();
            $type->state = mcodelti_TOOL_STATE_CONFIGURED;
            $data->mcodelti_coursevisible = 1;
            $data->mcodelti_sendname = mcodelti_SETTING_DELEGATE;
            $data->mcodelti_sendemailaddr = mcodelti_SETTING_DELEGATE;
            $data->mcodelti_acceptgrades = mcodelti_SETTING_DELEGATE;
            $data->mcodelti_forcessl = 0;

            if (!empty($key)) {
                $data->mcodelti_resourcekey = $key;
            }

            if (!empty($secret)) {
                $data->mcodelti_password = $secret;
            }

            mcodelti_load_type_from_cartridge($cartridgeurl, $data);
            if (empty($data->mcodelti_toolurl)) {
                throw new moodle_exception('unabletocreatetooltype', 'mod_mcodelti');
            } else {
                $id = mcodelti_add_type($type, $data);
            }
        }

        if (!empty($id)) {
            $type = mcodelti_get_type($id);
            return serialise_tool_type_mcode($type);
        } else {
            throw new moodle_exception('unabletocreatetooltype', 'mod_mcodelti');
        }
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function create_tool_type_returns() {
        return self::tool_type_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function update_tool_type_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
                'name' => new external_value(PARAM_RAW, 'Tool type name', VALUE_DEFAULT, null),
                'description' => new external_value(PARAM_RAW, 'Tool type description', VALUE_DEFAULT, null),
                'state' => new external_value(PARAM_INT, 'Tool type state', VALUE_DEFAULT, null)
            )
        );
    }

    /**
     * Update a tool type.
     *
     * @param int $id The id of the tool type to update
     * @param string $name The name of the tool type
     * @param string $description The name of the tool type
     * @param int $state The state of the tool type
     * @return array updated tool type
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function update_tool_type($id, $name, $description, $state) {
        $params = self::validate_parameters(self::update_tool_type_parameters(),
                                            array(
                                                'id' => $id,
                                                'name' => $name,
                                                'description' => $description,
                                                'state' => $state,
                                            ));
        $id = $params['id'];
        $name = $params['name'];
        $description = $params['description'];
        $state = $params['state'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $type = mcodelti_get_type($id);

        if (empty($type)) {
            throw new moodle_exception('unabletofindtooltype', 'mod_mcodelti', '', array('id' => $id));
        }

        if (!empty($name)) {
            $type->name = $name;
        }

        if (!empty($description)) {
            $type->description = $description;
        }

        if (!empty($state)) {
            // Valid state range.
            if (in_array($state, array(1, 2, 3))) {
                $type->state = $state;
            } else {
                throw new moodle_exception("Invalid state: $state - must be 1, 2, or 3");
            }
        }

        mcodelti_update_type($type, new stdClass());

        return serialise_tool_type_mcode($type);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function update_tool_type_returns() {
        return self::tool_type_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function delete_tool_type_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
            )
        );
    }

    /**
     * Delete a tool type.
     *
     * @param int $id The id of the tool type to be deleted
     * @return array deleted tool type
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function delete_tool_type($id) {
        $params = self::validate_parameters(self::delete_tool_type_parameters(),
                                            array(
                                                'id' => $id,
                                            ));
        $id = $params['id'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $type = mcodelti_get_type($id);

        if (!empty($type)) {
            mcodelti_delete_type($id);

            // If this is the last type for this proxy then remove the proxy
            // as well so that it isn't orphaned.
            $types = mcodelti_get_mcodelti_types_from_proxy_id($type->toolproxyid);
            if (empty($types)) {
                mcodelti_delete_tool_proxy($type->toolproxyid);
            }
        }

        return array('id' => $id);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function delete_tool_type_returns() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function is_cartridge_parameters() {
        return new external_function_parameters(
            array(
                'url' => new external_value(PARAM_URL, 'Tool url'),
            )
        );
    }

    /**
     * Determine if the url to a tool is for a cartridge.
     *
     * @param string $url Url that may or may not be an xml cartridge
     * @return bool True if the url is for a cartridge.
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function is_cartridge($url) {
        $params = self::validate_parameters(self::is_cartridge_parameters(),
                                            array(
                                                'url' => $url,
                                            ));
        $url = $params['url'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $iscartridge = mcodelti_is_cartridge($url);

        return array('iscartridge' => $iscartridge);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function is_cartridge_returns() {
        return new external_function_parameters(
            array(
                'iscartridge' => new external_value(PARAM_BOOL, 'True if the URL is a cartridge'),
            )
        );
    }
}
