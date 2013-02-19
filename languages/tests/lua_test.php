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
class qtype_scripted_language_lua_test extends advanced_testcase {

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

    public function test_execute_returns_standard_out() {
        $this->assertEquals(trim($this->lua->execute('print "Hello World"')), 'Hello World');
    }

    public function test_execute_modifies_environment() {
        $this->lua->execute("x = 7");
        $this->assertEquals($this->lua->x, 7); 
    }

    public function test_execute_accepts_existing_environment() {
        $lua = qtype_scripted_language_manager::create_interpreter('lua', array('x' => 3, 'y' => 4));
        $this->assertEquals(trim($lua->execute('print(x + y)')), 7);
    }

    public function test_evaluate_returns_value_of_expression() {
        $this->assertEquals($this->lua->evaluate('3 + 4'), 7);
    }

    public function test_evaluate_accepts_existing_environment() {
        $lua = qtype_scripted_language_manager::create_interpreter('lua', array('x' => 3, 'y' => 4));
        $this->assertEquals(trim($lua->evaluate('x + y')), 7);
    }

    public function test_evaluate_accepts_arrays_in_environment_as_tables() {
        $lua = qtype_scripted_language_manager::create_interpreter('lua', array('x' => array('y' => 3)));
        $this->assertEquals(trim($lua->evaluate('x.y')), 3);
    }

    public function test_get_variables_returns_all_user_created_variables() {
        $this->lua->execute('x = 3; y = 4');
        $this->assertEquals($this->lua->get_variables(), array('x' => 3, 'y' => 4));
    }

    public function test_get_variables_correctly_handles_nested_structures() {
        //Execute a short lua program, and ensure it produces the correct environment.
        $this->lua->execute('x = {1, 2, 3}; t = {x=3, y=4}');
        $expected_output = array(
            'x' => array(1 => 1, 2 => 2, 3 => 3),
            't' => array('x' => 3, 'y' => 4),
        );

        $this->assertEquals($this->lua->get_variables(), $expected_output);
    }

    public function test_set_variables_replaces_existing_environment() {
        $this->lua->set_variables(array('x' => 3, 'y' => 4));
        $this->assertEquals($this->lua->x, 3);
        $this->assertEquals($this->lua->y, 4);
    }

    public function test_summarize_variables_produces_flat_output() {
        
        //Set up a sample lua environment, and predict the output for that environment.
        $this->lua->execute('x = {1, 2, 3}; t = {x=3, y=4}; func = function() end');
        $expected_output = array(
            'x[1]' => 1,
            'x[2]' => 2,
            'x[3]' => 3,
            't.x' => 3,
            't.y' => 4,
            'func' => '<function #0>'
        );

        //Verify our predition.
        $this->assertEquals($this->lua->summarize_variables(), $expected_output);

    }

}
