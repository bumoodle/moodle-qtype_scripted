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
 * Test helpers for the scripted question type.
 *
 * @package    qtype_scripted
 * @copyright  2013 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the scripted question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_scripted_test_helper extends question_test_helper {

    public function get_test_questions() {
        return array('lua_numeric');
    }

    /**
     * Makes a scripted question whose answer is x + 3, where x = 3. 
     */
    public function make_scripted_question_lua_numeric() {
        
        question_bank::load_question_definition_classes('scripted');

        //Create a new Scripted question.
        $sc = new qtype_scripted_question();
        test_question_maker::initialise_a_question($sc);

        //Set up its options:
        $sc->name = 'Scripted question';
        $sc->questiontext = 'The number is currently {x}. What is {x} plus three?';
        $sc->generalfeedback = 'General Feedback: This addition was scripted.';
        $sc->init_code = 'x = 3; y = 4; z = 5.1';
        $sc->usecase = false;
        $sc->language = 'lua';
        $sc->answer_mode = qtype_scripted_answer_mode :: MODE_MUST_EQUAL;
        $sc->response_mode = qtype_scripted_response_mode :: MODE_NUMERIC;
        $sc->qtype = question_bank::get_qtype('scripted');

        //Create the potential answers...
        $sc->answers = array(
            13 => new question_answer(13, 'x + 3', 1.0, 'You added three correctly.', FORMAT_HTML),
            14 => new question_answer(14, 'x + 2', 0.8, 'You added two, which is almost three.', FORMAT_HTML),
            15 => new question_answer(15, 'x',     0.0, 'You didn\'t add at all.', FORMAT_HTML),
        );

        //And return the newly created question.
        return $sc;
    }

    /**
     * Gets the question data for a scripted question with with correct
     * ansewer 'frog', partially correct answer 'toad' and defaultmark 1.
     * This question also has a '*' match anything answer.
     * @return stdClass
     */
    public function get_scripted_question_data_lua_numeric() {
        $qdata = new stdClass();
        test_question_maker::initialise_question_data($qdata);

        $sc = $this->make_scripted_question_lua_numeric();

        $qdata->qtype = 'scripted';
        $qdata->name = $sc->name;
        $qdata->questiontext = $sc->questiontext;
        $qdata->generalfeedback = $sc->generalfeedback;

        //Copy the options from the question type.
        $qdata->options = new stdClass();
        foreach(array('init_code', 'usecase', 'language', 'answer_mode', 'response_mode', 'answers') as $property) {
            $qdata->options->$property = $sc->$property;
        }

        return $qdata;
    }

}
