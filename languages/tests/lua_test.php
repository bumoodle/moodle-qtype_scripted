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
 * Unit tests for the Lua scripting language interface.
 *
 * @package    qtype
 * @subpackage scripted
 * @copyright  2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/question/type/scripted/locallib.php');

/**
 * Unit tests for the scripted question definition class.
 *
 * @copyright  2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_scripted_question_test extends advanced_testcase {

    public function setUp() {
        $this->lua = qtype_scripted_language_manager::create_interpreter('lua');
    }

    public function test_execute_throws_exception_on_infinite_loop() {
        $this->setExpectedException('qtype_scripted_language_exception');
        $this->lua->execute("while true do end");
    }

    public function test_execute_throws_exception_on_long_running_code() {
        $this->setExpectedException('qtype_scripted_language_exception');
        $this->lua->execute("for i=1,math.huge do end");
    }


}
