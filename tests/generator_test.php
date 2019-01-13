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
 * PHPUnit data generator tests
 *
 * @package    mod_mcodelti
 * @category   phpunit
 * @copyright  Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @author     Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit data generator testcase
 *
 * @package    mod_mcodelti
 * @category   phpunit
 * @copyright  Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @author     Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mcodelti_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('mcodelti'));

        $course = $this->getDataGenerator()->create_course();

        /*
         * @var mod_mcodelti_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mcodelti');
        $this->assertInstanceOf('mod_mcodelti_generator', $generator);
        $this->assertEquals('mcodelti', $generator->get_modulename());

        $generator->create_instance(array('course' => $course->id));
        $generator->create_instance(array('course' => $course->id));
        $mcodelti = $generator->create_instance(array('course' => $course->id));
        $this->assertEquals(3, $DB->count_records('mcodelti'));

        $cm = get_coursemodule_from_instance('mcodelti', $mcodelti->id);
        $this->assertEquals($mcodelti->id, $cm->instance);
        $this->assertEquals('mcodelti', $cm->modname);
        $this->assertEquals($course->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($mcodelti->cmid, $context->instanceid);

        // Test gradebook integration using low level DB access - DO NOT USE IN PLUGIN CODE!
        $mcodelti = $generator->create_instance(array('course' => $course->id, 'assessed' => 1, 'scale' => 100));
        $gitem = $DB->get_record('grade_items', array('courseid' => $course->id, 'itemtype' => 'mod',
            'itemmodule' => 'mcodelti', 'iteminstance' => $mcodelti->id));
        $this->assertNotEmpty($gitem);
        $this->assertEquals(100, $gitem->grademax);
        $this->assertEquals(0, $gitem->grademin);
        $this->assertEquals(GRADE_TYPE_VALUE, $gitem->gradetype);
    }
}
