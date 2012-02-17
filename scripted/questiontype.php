<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/scripted/question.php');


/**
* Defines the scripted question type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_scripted extends question_type 
{
	/**
	 * Returns a list of the class fields which should be stored to the database.
	 */
    public function extra_question_fields() 
    {
        return array('question_scripted', 'answers', 'init_code', 'answer_mode', 'response_mode');
    }

    /**
     * Returns the name of the database column which stores the question's unique ID number.
     */
    public function questionid_column_name() 
    {
        return 'question';
    }

    /**
     * Stores any files present in the answer feedback, in their proper category, so they aren't identified as an orphan.
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) 
    {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }

    /**
     * Deletes any files present in answer feedback, in the event than a question or answer is deleted.
     */
    protected function delete_files($questionid, $contextid) 
    {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    /**
     * Saves the options for the current question.
     */
    public function save_question_options($question) 
    {
        global $DB;
        $result = new stdClass();

        $context = $question->context;

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        $answers = array();
        $maxfraction = -1;

        // Insert all the new answers
        foreach ($question->answer as $key => $answerdata) {
            // Check for, and ignore, completely blank answer from the form.
            if (trim($answerdata) == '' && $question->fraction[$key] == 0 &&
                    html_is_blank($question->feedback[$key]['text'])) {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer->answer   = trim($answerdata);
            $answer->fraction = $question->fraction[$key];
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];
            $DB->update_record('question_answers', $answer);

            $answers[] = $answer->id;
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }

        $question->answers = implode(',', $answers);
        $parentresult = parent::save_question_options($question);
        if ($parentresult !== null) {
            // Parent function returns null if all is OK
            return $parentresult;
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $this->save_hints($question);

        // Perform sanity checks on fractional grades
        if ($maxfraction != 1) 
        {
            $result->noticeyesno = get_string('fractionsnomax', 'question', $maxfraction * 100);
            return $result;
        }
    }

    
    /**
     * TODO: decypher meaning of moodle's PHPDoc
     * 
    * Initialise the common question_definition fields.
    * @param question_definition $question the question_definition we are creating.
    * @param object $questiondata the question data loaded from the database.
    */
    protected function initialise_question_instance(question_definition $question, $questiondata) 
    {
    	
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_answers($question, $questiondata);
    }

    /**
     * Get the possible score for a random guess.
     * 
     * //TODO: implement?
     */
    public function get_random_guess_score($questiondata) 
    {
        foreach ($questiondata->options->answers as $aid => $answer) {
            if ('*' == trim($answer->answer)) {
                return $answer->fraction;
            }
        }
        return 0;
    }

    //TODO: decypher?
    public function get_possible_responses($questiondata) 
    {
        $responses = array();

        foreach ($questiondata->options->answers as $aid => $answer) {
            $responses[$aid] = new question_possible_response($answer->answer,
                    $answer->fraction);
        }
        $responses[null] = question_possible_response::no_response();

        return array($questiondata->id => $responses);
    }
}
