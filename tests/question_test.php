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
 * Unit tests for the short answer question definition class.
 *
 * @package    qtype
 * @subpackage shortanswer
 * @copyright  2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/scripted/question.php');


/**
 * Unit tests for the scripted question definition class.
 *
 * @copyright  2013 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_scripted_question_test extends advanced_testcase {

   public function setUp() {
     $this->question = test_question_maker::make_question('scripted');
   }

   public function test_is_complete_response_string() {
         $question = $this->question;

         //Test string responses.
         $question->response_mode = qtype_scripted_response_mode::MODE_STRING;
         $this->assertFalse($question->is_complete_response(array()));
         $this->assertFalse($question->is_complete_response(array('answer' => '')));
         $this->assertTrue( $question->is_complete_response(array('answer' => '0')));
         $this->assertTrue( $question->is_complete_response(array('answer' => '0.0')));
         $this->assertTrue( $question->is_complete_response(array('answer' => 'x')));
   }

   public function test_is_complete_response_case_sensitive() {
         $question = $this->question;

          //Test case sensitive string responses..
         $question->response_mode = qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE;
         $this->assertFalse($question->is_complete_response(array()));
         $this->assertFalse($question->is_complete_response(array('answer' => '')));
         $this->assertTrue( $question->is_complete_response(array('answer' => '0')));
         $this->assertTrue( $question->is_complete_response(array('answer' => '0.0')));
         $this->assertTrue( $question->is_complete_response(array('answer' => 'x')));
   }


   public function test_is_complete_response_numeric() {
        $question = $this->question;
        
         //Test case sensitive string responses..
        $question->response_mode = qtype_scripted_response_mode::MODE_NUMERIC;
        $this->assertFalse($question->is_complete_response(array()));
        $this->assertFalse($question->is_complete_response(array('answer' => '')));
        $this->assertFalse($question->is_complete_response(array('answer' => 'm')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0.0')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '1.5')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '1.5e-7')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0xFF')));
   }

   public function test_is_complete_response_binary() {
        $question = $this->question;
        
         //Test case sensitive string responses..
        $question->response_mode = qtype_scripted_response_mode::MODE_BINARY;
        $this->assertFalse($question->is_complete_response(array()));
        $this->assertFalse($question->is_complete_response(array('answer' => 'm')));
        $this->assertFalse($question->is_complete_response(array('answer' => '0.0')));
        $this->assertFalse($question->is_complete_response(array('answer' => '012')));
        $this->assertFalse($question->is_complete_response(array('answer' => '0b7')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '01')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0b01')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '%1001')));

        //Allow a blank response to be equal to zero; this simplifies filling in large
        //truth tables.
        $this->assertTrue( $question->is_complete_response(array('answer' => '')));
   }


   public function test_is_complete_response_hexadecimal() {
        $question = $this->question;
        
         //Test case sensitive string responses..
        $question->response_mode = qtype_scripted_response_mode::MODE_HEXADECIMAL;
        $this->assertFalse($question->is_complete_response(array()));
        $this->assertFalse($question->is_complete_response(array('answer' => 'm')));
        $this->assertFalse($question->is_complete_response(array('answer' => '0.0')));
        $this->assertFalse($question->is_complete_response(array('answer' => '01G')));
        $this->assertFalse($question->is_complete_response(array('answer' => '0xZ')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0')));
        $this->assertTrue( $question->is_complete_response(array('answer' => 'AF')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0x0F')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '$D00D')));

        //Allow a blank response to be equal to zero; this simplifies filling in large
        //truth tables.
        $this->assertTrue( $question->is_complete_response(array('answer' => '')));
   }

   public function test_is_complete_response_octal() {
        $question = $this->question;
        
         //Test case sensitive string responses..
        $question->response_mode = qtype_scripted_response_mode::MODE_OCTAL;
        $this->assertFalse($question->is_complete_response(array()));
        $this->assertFalse($question->is_complete_response(array('answer' => 'm')));
        $this->assertFalse($question->is_complete_response(array('answer' => '0.0')));
        $this->assertFalse($question->is_complete_response(array('answer' => '19')));
        $this->assertFalse($question->is_complete_response(array('answer' => '0o9')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '67')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '0o67')));
        $this->assertTrue( $question->is_complete_response(array('answer' => '@1234')));

        //Allow a blank response to be equal to zero; this simplifies filling in large
        //truth tables.
        $this->assertTrue( $question->is_complete_response(array('answer' => '')));
   }

}
