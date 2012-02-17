<?php

// Calculated Sane Question Type
// (C) Binghamton University 2011
//
// author: Kyle Temkin <ktemkin@binghamton.edu>
//
// Modified from Moodle Core Code

///////////////////////
/// CALCULATED SANE ///
///////////////////////

/// QUESTION TYPE CLASS //////////////////

///
/// This class contains some special features in order to make the
/// question type embeddable within a multianswer (cloze) question
///
/**
 * @package   qtype_boolean
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/question/type/questiontypebase.php");
require_once("$CFG->dirroot/question/type/calculatedsane/evalmath.class.php");

class qtype_calculatedsane extends question_type 
{
	
	

	function name()
	{
		return 'calculatedsane';
	}

	function extra_question_fields() 
	{
		return array('question_calculatedsane', 'answers', 'init_code', 'answer_mode');
	}

	function questionid_column_name() 
	{	
		return 'question';
	}

	function move_files($questionid, $oldcontextid, $newcontextid) 
	{
		parent::move_files($questionid, $oldcontextid, $newcontextid);
		$this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
	}

	protected function delete_files($questionid, $contextid) 
	{
		parent::delete_files($questionid, $contextid);
		$this->delete_files_in_answers($questionid, $contextid);
	}

	function save_question_options($question) 
	{
		global $DB;
		$result = new stdClass;

		$context = $question->context;

		$oldanswers = $DB->get_records('question_answers',
		array('question' => $question->id), 'id ASC');

		// Insert all the new answers
		$answers = array();
		$maxfraction = -1;
		foreach ($question->answer as $key => $answerdata) 
		{

			// Check for, and ignore, completely blank answer from the form.
			if (trim($answerdata) == '' && $question->fraction[$key] == 0 && html_is_blank($question->feedback[$key]['text'])) 
				continue;

			// Update an existing answer if possible.
			$answer = array_shift($oldanswers);
			
			if (!$answer) 
			{
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

		// Delete any left over old answer records.
		$fs = get_file_storage();
		foreach($oldanswers as $oldanswer) {
			$fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
			$DB->delete_records('question_answers', array('id' => $oldanswer->id));
		}

		$question->answers = implode(',', $answers);
		$parentresult = parent::save_question_options($question);
		if ($parentresult !== null) {
			// Parent function returns null if all is OK
			return $parentresult;
		}

		// Perform sanity checks on fractional grades
		if ($maxfraction != 1) {
			$result->noticeyesno = get_string('fractionsnomax', 'quiz', $maxfraction * 100);
			return $result;
		}

		return true;
	}
	
	static function find_all_variables($text)
	{
		//extract all items of the form {[A-Za-z]+}, which are our variables
		$variables = preg_match_all("|\{([A-Za-z])+\}|", $text, $matches, PREG_SET_ORDER);
		
		//return the first element of each match- the variable name without the curly braces
		return array_map(function($arr) { return $arr[1]; },  $matches);
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see default_questiontype::create_session_and_responses()
	 * 
	 * Initializes an instance of the question by running the initialization code.
	 */
	function create_session_and_responses(&$question, &$state, $cmoptions, $attempt)
	{
		//no response, by default
		$state->responses = array('' => '');
		
		//create a EvalMath mathematics evaulator
		$m = new EvalMath();
		$m->suppress_errors = true;
		
		//find all variables in the question text, and initialize them to 0
		$variables = self::find_all_variables($question->questiontext);
		
		//initialize each variable to zero in case they aren't overridden in the init script
		foreach($variables as $var)
			$m->evaluate($var .'=0');
				
		//run the initlization "script"
		$errors = $m->evaluate_script($question->options->init_code);
		
		
		//store the list of variables after the execution
		$state->options->vars = $m->vars();
		$state->options->funcs = $m->funcs_raw();
		
		//indicate success
		return true;
	}
	
	function save_session_and_responses(&$question, &$state)
	{
		global $DB;
		
		//save the current response
		if(isset($state->responses['']))
			$response = $state->responses[''];
		else
			$response = '';
		
		//store functions and variables in the legacy answer field
		$eval_data = self::serialize_eval_data($response, $state->options->vars, $state->options->funcs);
		$DB->set_field('question_states', 'answer', $eval_data, array('id' => $state->id));
		
		//indicate success
		return true;
	}
	
		
	function restore_session_and_responses(&$question, &$state)
	{
		global $DB;
		
		//get the previous functions/variables from the database
		$eval_data = $DB->get_field('question_states', 'answer', array('id'=> $state->id));
		
		//parse the serialized function and variables
		list($response, $vars, $funcs) = self::unserialize_eval_data($eval_data);
		
		//and repopulate the question state
		$state->responses[''] = $response;
		$state->options->vars = $vars;
		$state->options->funcs = $funcs;
		 
		//indicate success
		return true;
	}
	
	
	static function serialize_eval_data($response, $vars, $funcs)
	{
		//FIXME: don't use serialize (for security?)
		return serialize(array($response, $vars, $funcs));
	}
	
	static function unserialize_eval_data($serialized)
	{
		//FIXME: don't use serialize (for security?)
		return unserialize($serialized);
	}
	
	function question_text($question, $state)
	{
		$text = $question->questiontext;
		
		//replace each variable in the text with its value
		foreach($state->options->vars as $name => $value)
			$text = str_replace('{'.$name.'}', $value, $text);

		return $text;
	}
	

	function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) 
	{

		global $CFG;
		
		$context = $this->get_context_by_category_id($question->category);
		
		/// This implementation is also used by question type 'numerical'
		$readonly = empty($options->readonly) ? '' : 'readonly="readonly"';
		$formatoptions = new stdClass;
		$formatoptions->noclean = true;
		$formatoptions->para = false;
		$nameprefix = $question->name_prefix;

		/// Print question text and media

		$questiontext = format_text($this->question_text($question, $state), $question->questiontextformat,	$formatoptions, $cmoptions->course);

		/// Print input controls

		if (isset($state->responses['']) && $state->responses['']!='') {
			$value = ' value="'.s($state->responses['']).'" ';
		} else {
			$value = ' value="" ';
		}
		$inputname = ' name="'.$nameprefix.'" ';

		$feedback = '';
		$class = '';
		$feedbackimg = '';

		if ($options->feedback) 
		{
			$class = question_get_feedback_class(0);
			$feedbackimg = question_get_feedback_image(0);			
			
			//this is OK for the first answer with a good response
			foreach($question->options->answers as $answer) 
			{

				//if the response matches a pattern,
				if ($this->test_response($question, $state, $answer))
				{
					//the answer was correct or partially correct.
					$class = question_get_feedback_class($answer->fraction);
					$feedbackimg = question_get_feedback_image($answer->fraction);
					
					//if feedback was provided, display it
					if ($answer->feedback) 
					{
						$answer->feedback = quiz_rewrite_question_urls($answer->feedback, 'pluginfile.php', $context->id, 'question', 'answerfeedback', array($state->attempt, $state->question), $answer->id);
						$feedback = format_text($answer->feedback, $answer->feedbackformat, $formatoptions, $cmoptions->course);
					}
					break;
				}
			}
		}

		/// Removed correct answer, to be displayed later MDL-7496
		include($this->get_display_html_path());
	}

	function get_display_html_path() {
		global $CFG;
		return $CFG->dirroot.'/question/type/boolean/display.html';
	}

	function check_response(&$question, &$state) 
	{
		foreach($question->options->answers as $aid => $answer) 
		{
			if ($this->test_response($question, $state, $answer)) 
			{
				return $aid;
			}
		}
		return false;
	}

	function compare_responses($question, $state, $teststate) 
	{
		if (isset($state->responses['']) && isset($teststate->responses[''])) 
		{
			return $state->responses[''] === $teststate->responses[''];
		}
		return false;
	}

	function test_response(&$question, $state, $answer) 
	{		
		//if we have a non-numeric answer
		if(empty($state->responses['']) || !is_numeric($state->responses['']))
			return false;
		
		/*
			//if the number is a hex value, parse it as such
			if(substr($state->responses[''], 0, 2) === "0x")	
				$value = dechex(intval(substr($state->responses[''], 2), 16));
			else
				$value = intval($state->responses['']);
		*/
		//if the number is a hex value, re-encode it in the same form as dechex output
		if(substr($state->responses[''], 0, 2) === "0x")
			$value = dechex(intval(substr($state->responses[''], 2), 16));
		else
			$value = $state->responses[''];
		
		
		return $this->compare_to_value($question, $state, $answer, $value);
	}

	function compare_to_value(&$question, &$state, $answer, $value)
	{
		
		//create a new math evaluation object
		$m = new EvalMath();
		$m->suppress_errors = true;
		
		//define all known functions and variables (defined in a previous attempt or init script)
		$m->vars($state->options->vars);
		$m->funcs_raw($state->options->funcs);
		
		
		switch($question->options->answer_mode)
		{
			//for direct/answer modes,
			case 'direct':
			case 'answer':
							
			//evaluate the given answer formula
			$ans = $m->evaluate($answer->answer);
			
			//the response is correct iff it evaluates to the same value as the answer:
			
			//if the two are both numeric, compare them loosely, without regard to type; so 5 == "05" is true
			if(is_numeric($ans) && is_numeric($value))
			{
				return $ans == $value;
			}
			//otherwise, compare them stricly; so "5a" !== 5; (note that, had we performed a loose compare, "5a" == 5 is true due to type juggling >.<)
			else
			{
				return $ans === $value;
			}
		
		//for boolean modes,
		case 'boolean':
		
			//we define the answer variable "ans",
			$m->evaluate('ans='.$value);
			
			//and return true iff the answer evaluates to True
			return $m->evaluate($answer->answer);
		
		default:
						
			//something's gone wrong
			throw new coding_exception('Invalid grading mode for calculated sane qtype.');
		
		}
	
	}
	

	/**
	 * @param string response is a response.
	 * @return formatted response
	 */
	function format_response($response, $format){
		return s($response);
	}

	/*
	 * Override the parent class method, to remove escaping from asterisks.
	 */
	function get_correct_responses(&$question, &$state) 
	{
		$response = parent::get_correct_responses($question, $state);
		$correct = array();

		//get a new math evaluator object
		$m = new EvalMath();
		$m->suppress_errors = true;
		
		//for each possible answer
		foreach($response as $r)
		{				
			//reset the vars and functions (done every time so the answers don't intefere with each other)
			$m->vars($state->options->vars);
			$m->funcs_raw($state->options->funcs);
			
			//and get the correct answer after it's been evaluated
			$correct[] = $m->evaluate($r);
		}
		
		
		return $correct;
		 
	}
	/**
	 * @param object $question
	 * @return mixed either a integer score out of 1 that the average random
	 * guess by a student might give or an empty string which means will not
	 * calculate.
	 */
	function get_random_guess_score($question) 
	{
		return 0;
	}

	/**
	 * Prints the score obtained and maximum score available plus any penalty
	 * information
	 *
	 * This function prints a summary of the scoring in the most recently
	 * graded state (the question may not have been submitted for marking at
	 * the current state). The default implementation should be suitable for most
	 * question types.
	 * @param object $question The question for which the grading details are
	 *                         to be rendered. Question type specific information
	 *                         is included. The maximum possible grade is in
	 *                         ->maxgrade.
	 * @param object $state    The state. In particular the grading information
	 *                          is in ->grade, ->raw_grade and ->penalty.
	 * @param object $cmoptions
	 * @param object $options  An object describing the rendering options.
	 */
	function print_question_grading_details(&$question, &$state, $cmoptions, $options) {
		/* The default implementation prints the number of marks if no attempt
		 has been made. Otherwise it displays the grade obtained out of the
		 maximum grade available and a warning if a penalty was applied for the
		 attempt and displays the overall grade obtained counting all previous
		 responses (and penalties) */

		global $QTYPES ;
		// MDL-7496 show correct answer after "Incorrect"
		$correctanswer = '';
		if ($correctanswers =  $QTYPES[$question->qtype]->get_correct_responses($question, $state)) {
			if ($options->readonly && $options->correct_responses) {
				$delimiter = '';
				if ($correctanswers) {
					foreach ($correctanswers as $ca) 
					{
						$correctanswer .= $delimiter.$ca;
						$delimiter = ', ';
					}
				}
			}
		}

		if (QUESTION_EVENTDUPLICATE == $state->event) {
			echo ' ';
			print_string('duplicateresponse', 'quiz');
		}
		if ($question->maxgrade > 0 && $options->scores) {
			if (question_state_is_graded($state->last_graded)) {
				// Display the grading details from the last graded state
				$grade = new stdClass;
				$grade->cur = question_format_grade($cmoptions, $state->last_graded->grade);
				$grade->max = question_format_grade($cmoptions, $question->maxgrade);
				$grade->raw = question_format_grade($cmoptions, $state->last_graded->raw_grade);
				// let student know wether the answer was correct
				$class = question_get_feedback_class($state->last_graded->raw_grade /
				$question->maxgrade);
				echo '<div class="correctness ' . $class . '">' . get_string($class, 'quiz');
				if ($correctanswer  != '' && ($class == 'partiallycorrect' || $class == 'incorrect')) {
					echo ('<div class="correctness">');
					print_string('correctansweris', 'quiz', s($correctanswer));
					echo ('</div>');
				}
				echo '</div>';

				echo '<div class="gradingdetails">';
				// print grade for this submission
				print_string('gradingdetails', 'quiz', $grade) ;
				// A unit penalty for numerical was applied so display it
				// a temporary solution for unit rendering in numerical
				// waiting for the new question engine code for a permanent one
				if(isset($state->options->raw_unitpenalty) && $state->options->raw_unitpenalty > 0.0 ){
					echo ' ';
					print_string('unitappliedpenalty','qtype_numerical',question_format_grade($cmoptions, $state->options->raw_unitpenalty * $question->maxgrade ));
				}
				if ($cmoptions->penaltyscheme) {
					// print details of grade adjustment due to penalties
					if ($state->last_graded->raw_grade > $state->last_graded->grade){
						echo ' ';
						print_string('gradingdetailsadjustment', 'quiz', $grade);
					}
					// print info about new penalty
					// penalty is relevant only if the answer is not correct and further attempts are possible
					if (($state->last_graded->raw_grade < $question->maxgrade) and (QUESTION_EVENTCLOSEANDGRADE != $state->event)) {
						if ('' !== $state->last_graded->penalty && ((float)$state->last_graded->penalty) > 0.0) {
							echo ' ' ;
							print_string('gradingdetailspenalty', 'quiz', question_format_grade($cmoptions, $state->last_graded->penalty));
						} else {
							/* No penalty was applied even though the answer was
							 not correct (eg. a syntax error) so tell the student
							 that they were not penalised for the attempt */
							echo ' ';
							print_string('gradingdetailszeropenalty', 'quiz');
						}
					}
				}
				echo '</div>';
			}
		}
	}

	/**
	 * Runs all the code required to set up and save an essay question for testing purposes.
	 * Alternate DB table prefix may be used to facilitate data deletion.
	 */
	function generate_test($name, $courseid = null) {
		global $DB;
		list($form, $question) = parent::generate_test($name, $courseid);
		$question->category = $form->category;

		$form->questiontext = "What is the purpose of life, the universe, and everything";
		$form->generalfeedback = "Congratulations, you may have solved my biggest problem!";
		$form->penalty = 0.1;
		$form->usecase = false;
		$form->defaultgrade = 1;
		$form->noanswers = 3;
		$form->answer = array('42', 'who cares?', 'Be happy');
		$form->fraction = array(1, 0.6, 0.8);
		$form->feedback = array('True, but what does that mean?', 'Well you do, dont you?', 'Yes, but thats not funny...');
		$form->correctfeedback = 'Excellent!';
		$form->incorrectfeedback = 'Nope!';
		$form->partiallycorrectfeedback = 'Not bad';

		if ($courseid) {
			$course = $DB->get_record('course', array('id' => $courseid));
		}

		return $this->save_question($question, $form);
	}

	function check_file_access($question, $state, $options, $contextid, $component, $filearea, $args)
	 {
		if ($component == 'question' && $filearea == 'answerfeedback') {
			$answers = &$question->options->answers;
			if (isset($state->responses[''])) {
				$response = $state->responses[''];
			} else {
				$response = '';
			}
			$answerid = reset($args); // itemid is answer id.
			if (empty($options->feedback)) {
				return false;
			}
			foreach($answers as $answer) {
				if ($this->test_response($question, $state, $answer)) 
				{
					return true;
				}
			}
			return false;

		} else {
			return parent::check_file_access($question, $state, $options, $contextid, $component,
			$filearea, $args);
		}
	}

}
//// END OF CLASS ////

//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_calculatedsane_qtype());