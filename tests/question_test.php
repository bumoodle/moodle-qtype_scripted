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
 
    /**
     * Helper function which verifies the "in_complete_repsonse" function in a given mode.
     * Intended to be used with a data provider.
     *
     * If answer is false, an empty array will be used.
     */ 
    private function is_complete_response_in_mode($mode, $should_return, $answer, $line=null) {

        //Put the test question into the given mode...
        $this->question->response_mode = $mode;

        //Create an error message which will be returned if the assertion fails...
        $message = "is_complete_response should have returned $should_return for input $answer";

        //If we were provided with a line number, copy it.
        if($line) {
            $message = " on line $line";
        }

        //Create the answer to assert against.
        $answer = ($answer !== false) ? array('answer' => $answer) : array();

        //And ensure we have the value we expect.
        $this->assertEquals($should_return, $this->question->is_complete_response($answer), $message);
    }

    //Generates test cases for is_complete_response in string mode.
    public function is_complete_response_string_provider() {
        return array(
            array(false, false),
            array(false, ''),
            array(true, '0'),
            array(true, '0.0'),
            array(true, 'x')
        );
    }


    /** @dataProvider is_complete_response_string_provider */ 
    public function test_is_complete_response_identifies_nonempty_strings_in_string_mode($should_return, $answer) {
        $this->is_complete_response_in_mode(qtype_scripted_response_mode::MODE_STRING, $should_return, $answer);
    }

    /** @dataProvider is_complete_response_string_provider */
    public function test_is_complete_response_identifies_nonempty_strings_in_case_sensitive_mode($should_return, $answer) {
        $this->is_complete_response_in_mode(qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE, $should_return, $answer);
    }

    //Generates test cases for is_complete_response in numeric mode.
    public function is_complete_response_numeric_provider() {
        return array(
            array(false, false),
            array(false, ''),
            array(false, 'm'),
            array(true, '0'),
            array(true, '0.0'),
            array(true, '1.5'),
            array(true, '1.5e-7'),
            array(true, '1.5e-7'),
            array(true, '0xFF')
        );
    }

    /** @dataProvider is_complete_response_numeric_provider */
    public function test_is_complete_response_accepts_only_valid_numbers_in_numeric_mode($should_return, $answer) {
        $this->is_complete_response_in_mode(qtype_scripted_response_mode::MODE_NUMERIC, $should_return, $answer);
    }
 
    //Generates test cases for is_complete_response in binary mode.
    public function is_complete_response_binary_provider() {
        return array(
            array(false, false),
            array(false, 'm'),
            array(false, '0.0'),
            array(false, '012'),
            array(false, '0b7'),
            array(true, '0'),
            array(true, '01'),
            array(true, '0b01'),
            array(true, '%1001'),
            array(true, ''), //allow empty strings; this provides easy fill-ins of truth tables
        );
    }
 
    /** @dataProvider is_complete_response_binary_provider */
    public function test_is_complete_response_accepts_only_valid_binary_in_binary_mode($should_return, $answer) {
        $this->is_complete_response_in_mode(qtype_scripted_response_mode::MODE_BINARY, $should_return, $answer);
    }

    //Generates test cases for is_complete_response in hex mode.
    public function is_complete_response_hexadecimal_provider() {
        return array(
            array(false, false),
            array(false, ''),
            array(false, 'm'),
            array(false, '0.0'),
            array(false, '01G'),
            array(false, '0xZ'),
            array(true, '0'),
            array(true, 'AF'),
            array(true, '0x0F'),
            array(true, '$D00D'),
            array(true, '0xFF')
        );
    }

    /** @dataProvider is_complete_response_hexadecimal_provider */
    public function test_is_complete_response_accepts_only_valid_hex_in_hexadecimal_mode($should_return, $answer) {
        $this->is_complete_response_in_mode(qtype_scripted_response_mode::MODE_HEXADECIMAL, $should_return, $answer);
    }

    //Generates test cases for is_complete_response in hex mode.
    public function is_complete_response_octal_provider() {
        return array(
            array(false, false),
            array(false, ''),
            array(false, 'm'),
            array(false, '0.0'),
            array(false, '019'),
            array(false, '0o9'),
            array(true, '0'),
            array(true, '67'),
            array(true, '0o67'),
            array(true, '@1234')
        );
    }

    /** @dataProvider is_complete_response_octal_provider */
    public function test_is_complete_response_accepts_only_valid_hex_in_octal_mode($should_return, $answer) {
        $this->is_complete_response_in_mode(qtype_scripted_response_mode::MODE_OCTAL, $should_return, $answer);
    }
 
    public function test_start_attempt_runs_the_init_script() {
 
        $step = new question_attempt_step();
        $this->question->start_attempt($step, 0);
 
        //Retrieve the variables that were created while the attempt was started, 
        //and ensure they match our init_code.
        $vars = $this->question->safe_unserialize($step->get_qt_var('_vars'));
        $this->assertEquals($vars, array('x' => 3, 'y' => 4, 'z' => 5.1));
    }
 
    public function test_summarize_response_returns_values_correctly() {
        //Ensure a non-empty response is returned directly...
        $this->assertEquals($this->question->summarise_response(array('answer' => '3.47')), '3.47');
 
        //And ensure an empty response returns null.
        $this->assertNull($this->question->summarise_response(array()));
    }
 
    /**
     * Tests for get_validation_error.
     */ 
 
    public function test_get_validation_error_returns_correct_message_for_valid_response() {
        $this->assertEmpty($this->question->get_validation_error(array('answer' => '3'))); 
    }
 
    public function test_get_validation_error_returns_correct_message_for_invalid_numeric() {
        $error = $this->question->get_validation_error(array('answer' => 'blah!'));
        $this->assertEquals($error, get_string('invalid_numeric', 'qtype_scripted'));
    }
 
    public function test_get_validation_error_returns_correct_message_for_invalid_binary() {
        $this->question->response_mode = qtype_scripted_response_mode::MODE_BINARY;
        $error = $this->question->get_validation_error(array('answer' => 'blah!'));
        $this->assertEquals($error, get_string('invalid_binary', 'qtype_scripted'));
    }
 
    public function test_get_validation_error_returns_correct_message_for_invalid_hexadecimal() {
        $this->question->response_mode = qtype_scripted_response_mode::MODE_HEXADECIMAL;
        $error = $this->question->get_validation_error(array('answer' => 'blah!'));
        $this->assertEquals($error, get_string('invalid_hexadecimal', 'qtype_scripted'));
    } 
    public function test_get_validation_error_returns_correct_message_for_invalid_octal() {
        $this->question->response_mode = qtype_scripted_response_mode::MODE_OCTAL;
        $error = $this->question->get_validation_error(array('answer' => 'blah!'));
        $this->assertEquals($error, get_string('invalid_octal', 'qtype_scripted'));
    }
 
    public function test_get_validation_error_returns_correct_message_for_invalid_string() {
        $this->question->response_mode = qtype_scripted_response_mode::MODE_STRING;
        $error = $this->question->get_validation_error(array('answer' => ''));
        $this->assertEquals($error, get_string('pleaseenterananswer', 'qtype_shortanswer'));
    }
 
 
    public function test_get_validation_error_returns_correct_message_for_invalid_case_sensitive_string() {
        $this->question->response_mode = qtype_scripted_response_mode::MODE_STRING_CASE_SENSITIVE;
        $error = $this->question->get_validation_error(array('answer' => ''));
        $this->assertEquals($error, get_string('pleaseenterananswer', 'qtype_shortanswer'));
    }
 
    /***
     * Tests for compare-response-with-answer.
     */ 

    public function compare_response_matches_equals_provider() {
       return array(
           //Should Match?     Answer (Expression)         Student Response
           array(true,         'x + 3',                    '6'),
           array(true,         'x + 2',                    '5'),
           array(true,         'x + 2',                    '05'),
           array(true,         'x - x',                    '0'),
           array(true,         'x + 7',                    '10blah'),
           array(false,        'x * 2',                    'six'),
           array(false,        'x',                        'a3'),
           array(false,        'x - x',                    ''),
           array(false,        'x .. "a"',                 '3a')
       );
    }

    private function verify_compare_response_in_mode($mode, $should_return, $answer, $response, $line = null) {
    
        //start the question, filling in the value for x
        $this->question->start_attempt(new question_attempt_step(), 0);
        $this->question->answer_mode = $mode;

        //Build a message for if the test fails...
        $message = "A response of $response should be equal to the answer $answer";
        if($line) {
            $message .= " on line $line";
        }

        //Build the answer and the response...
        $answer = new question_answer(1, $answer, 1.0, '', FORMAT_HTML);
        $response = array('answer' => $response);

        //And test them.
        $this->assertEquals($should_return, $this->question->compare_response_with_answer($response, $answer), $message);
    }

    /** @dataProvider compare_response_matches_equals_provider */
    public function test_compare_response_matches_using_equals_in_equals_mode($should_return, $answer, $response) {
       $this->verify_compare_response_in_mode(qtype_scripted_answer_mode::MODE_MUST_EQUAL, $should_return, $answer, $response, __LINE__);
    }

    //Generate test cases for the eval_true mode.
    public function compare_response_matches_evals_true_provider() {
       return array(
           //Should Match?     Answer (Expression)         Student Response
           array(true,         'resp == x + 3',            '6'),
           array(true,         'resp == x + 2',            '5'),
           array(true,         '(resp / 5) == 1',          '05'),
           array(true,         'resp ~= 1',                '0'),
           array(true,         'resp .. "a" == "3a"',      '3'),
           array(false,        'resp == x * 2',            'six'),
           array(false,        'resp + 1 == 2',            'a3'),
           array(false,        'resp % 3 == 0',            ''),
           array(false,        'resp .. "a" == "ab"',      '3a')
       );
    }

    /** @dataProvider compare_response_matches_evals_true_provider */
    public function test_compare_response_matches_using_equals_in_evals_true_mode($should_return, $answer, $response) {
        $this->verify_compare_response_in_mode(qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE, $should_return, $answer, $response, __LINE__);
    }

    /**
     * Tests for formatting question text.
     */ 

    public function format_questiontext_provider() {

        $errortext = get_string('error_placeholder', 'qtype_scripted');

        return array(
                   //Original Text                                 //Evaluated
           array(  '{x} text {y} words {z}',                       '3 text 4 words 5.1'   ),
           array(  '{x+1} text {y-1} words {math.floor(z)}',       '4 text 3 words 5'     ),
           array(  '{{print(x)}} <b>{y}</b> {{print "hello"}}',    "3\n <b>4</b> hello\n"   ),
           array(  'without variables',                            'without variables'    ),
           array(  '{{ errorfail }} {badnews}',                    "$errortext $errortext"),
       );
    }

    /** @dataProvider format_questiontext_provider */
    public function test_format_questiontext_evaluates_inline_variables($original, $evaluated) {

        //Create a new attempt at a scripted question...
        $attempt = new question_attempt($this->question, 1);
        $attempt->start('adaptive', 0);

        //Set the question text...
        $this->question->questiontext = $original;

        //And ensure we get back the proper values.
        $this->assertEquals($this->question->format_questiontext($attempt), $evaluated);
    }

    /**
     * Tests for get_correct_response.
     */ 
    public function get_correct_response_provider() {
        return array(
                      //Answer pattern        //Evaluated       //Distractor 
            array(    'x + 3',                '6',              'x + 2'), 
            array(    'x + 4',                '7',              'x'),
            array(    'x - x',                '0',              '1')
        );
    }

    /** @dataProvider get_correct_response_provider */
    public function test_get_correct_response_returns_evaluated_in_equals_mode($answer, $evaluated, $distractor) {

        //Start the question...
        $this->question->start_attempt(new question_attempt_step(), 1);

        //Populate this question's answers...
        $this->question->answers = array(
            13 => new question_answer(13, $answer, 1.0, '', FORMAT_HTML),
            14 => new question_answer(14, $distractor, 0.8, '', FORMAT_HTML),
        );

        //Ensure we get back the correct response.
        $this->assertEquals($this->question->get_correct_response(), array('answer' => $evaluated));
    }

    /** @dataProvider get_correct_response_provider */
    public function test_get_correct_response_returns_null_in_eval_true_mode($answer, $evaluated, $distractor) {

        //Start the question...
        $this->question->start_attempt(new question_attempt_step(), 1);
        $this->question->answer_mode = qtype_scripted_answer_mode::MODE_MUST_EVAL_TRUE;

        //Populate this question's answers...
        $this->question->answers = array(
            13 => new question_answer(13, $answer, 1.0, '', FORMAT_HTML),
            14 => new question_answer(14, $distractor, 0.8, '', FORMAT_HTML),
        );

        //Ensure we get back the correct response.
        $this->assertNull($this->question->get_correct_response());
    }

}
