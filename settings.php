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
 * This file defines the global mcodelti administration form
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

/*
 * @var admin_settingpage $settings
 */
$modmcodeltifolder = new admin_category('modmcodeltifolder', new lang_string('pluginname', 'mod_mcodelti'), $module->is_enabled() === false);
$ADMIN->add('modsettings', $modmcodeltifolder);
$settings->visiblename = new lang_string('manage_tools', 'mod_mcodelti');
$settings->hidden = true;
$ADMIN->add('modmcodeltifolder', $settings);
$proxieslink = new admin_externalpage('mcodeltitoolproxies',
        get_string('manage_tool_proxies', 'mcodelti'),
        new moodle_url('/mod/mcodelti/toolproxies.php'));
$proxieslink->hidden = true;
$ADMIN->add('modmcodeltifolder', $proxieslink);
$ADMIN->add('modmcodeltifolder', new admin_externalpage('mcodeltitoolconfigure',
        get_string('manage_external_tools', 'mcodelti'),
        new moodle_url('/mod/mcodelti/toolconfigure.php')));

foreach (core_plugin_manager::instance()->get_plugins_of_type('mcodeltisource') as $plugin) {
    /*
     * @var \mod_mcodelti\plugininfo\mcodeltisource $plugin
     */
    $plugin->load_settings($ADMIN, 'modmcodeltifolder', $hassiteconfig);
}

$toolproxiesurl = new moodle_url('/mod/mcodelti/toolproxies.php');
$toolproxiesurl = $toolproxiesurl->out();

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/mcodelti/locallib.php');

    $configuredtoolshtml = '';
    $pendingtoolshtml = '';
    $rejectedtoolshtml = '';

    $active = get_string('active', 'mcodelti');
    $pending = get_string('pending', 'mcodelti');
    $rejected = get_string('rejected', 'mcodelti');

    // Gather strings used for labels in the inline JS.
    $PAGE->requires->strings_for_js(
        array(
            'typename',
            'baseurl',
            'action',
            'createdon'
        ),
        'mod_mcodelti'
    );

    $types = mcodelti_filter_get_types(get_site()->id);

    $configuredtools = mcodelti_filter_tool_types($types, mcodelti_TOOL_STATE_CONFIGURED);

    $configuredtoolshtml = mcodelti_get_tool_table($configuredtools, 'mcodelti_configured');

    $pendingtools = mcodelti_filter_tool_types($types, mcodelti_TOOL_STATE_PENDING);

    $pendingtoolshtml = mcodelti_get_tool_table($pendingtools, 'mcodelti_pending');

    $rejectedtools = mcodelti_filter_tool_types($types, mcodelti_TOOL_STATE_REJECTED);

    $rejectedtoolshtml = mcodelti_get_tool_table($rejectedtools, 'mcodelti_rejected');

    $tab = optional_param('tab', '', PARAM_ALPHAEXT);
    $activeselected = '';
    $pendingselected = '';
    $rejectedselected = '';
    switch ($tab) {
        case 'mcodelti_pending':
            $pendingselected = 'class="selected"';
            break;
        case 'mcodelti_rejected':
            $rejectedselected = 'class="selected"';
            break;
        default:
            $activeselected = 'class="selected"';
            break;
    }
    $addtype = get_string('addtype', 'mcodelti');
    $config = get_string('manage_tool_proxies', 'mcodelti');

    $addtypeurl = "{$CFG->wwwroot}/mod/mcodelti/typessettings.php?action=add&amp;sesskey={$USER->sesskey}";

    $template = <<< EOD
<div id="mcodelti_tabs" class="yui-navset">
    <ul id="mcodelti_tab_heading" class="yui-nav" style="display:none">
        <li {$activeselected}>
            <a href="#tab1">
                <em>$active</em>
            </a>
        </li>
        <li {$pendingselected}>
            <a href="#tab2">
                <em>$pending</em>
            </a>
        </li>
        <li {$rejectedselected}>
            <a href="#tab3">
                <em>$rejected</em>
            </a>
        </li>
    </ul>
    <div class="yui-content">
        <div>
            <div><a style="margin-top:.25em" href="{$addtypeurl}">{$addtype}</a></div>
            $configuredtoolshtml
        </div>
        <div>
            $pendingtoolshtml
        </div>
        <div>
            $rejectedtoolshtml
        </div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
    YUI().use('yui2-tabview', 'yui2-datatable', function(Y) {
        //If javascript is disabled, they will just see the three tabs one after another
        var mcodelti_tab_heading = document.getElementById('mcodelti_tab_heading');
        mcodelti_tab_heading.style.display = '';

        new Y.YUI2.widget.TabView('mcodelti_tabs');

        var setupTools = function(id, sort){
            var mcodelti_tools = Y.YUI2.util.Dom.get(id);

            if(mcodelti_tools){
                var dataSource = new Y.YUI2.util.DataSource(mcodelti_tools);

                var configuredColumns = [
                    {key:'name', label: M.util.get_string('typename', 'mod_mcodelti'), sortable: true},
                    {key:'baseURL', label: M.util.get_string('baseurl', 'mod_mcodelti'), sortable: true},
                    {key:'timecreated', label: M.util.get_string('createdon', 'mod_mcodelti'), sortable: true},
                    {key:'action', label: M.util.get_string('action', 'mod_mcodelti')}
                ];

                dataSource.responseType = Y.YUI2.util.DataSource.TYPE_HTMLTABLE;
                dataSource.responseSchema = {
                    fields: [
                        {key:'name'},
                        {key:'baseURL'},
                        {key:'timecreated'},
                        {key:'action'}
                    ]
                };

                new Y.YUI2.widget.DataTable(id + '_container', configuredColumns, dataSource,
                    {
                        sortedBy: sort
                    }
                );
            }
        };

        setupTools('mcodelti_configured_tools', {key:'name', dir:'asc'});
        setupTools('mcodelti_pending_tools', {key:'timecreated', dir:'desc'});
        setupTools('mcodelti_rejected_tools', {key:'timecreated', dir:'desc'});
    });
//]]
</script>
EOD;
    $settings->add(new admin_setting_heading('mcodelti_types', new lang_string('external_tool_types', 'mcodelti') .
        $OUTPUT->help_icon('main_admin', 'mcodelti'), $template));
}

// Tell core we already added the settings structure.
$settings = null;

