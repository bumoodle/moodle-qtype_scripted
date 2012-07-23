<?php

/**
 * Calculated question definition class.
 *
 * @package    qtype
 * @subpackage calculatedsane
 * @copyright  2011 Binghamton University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author 	   Kyle Temkin <ktemkin@binghamton.edu>
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/numerical/question.php');


/**
 * Represents a calculated question.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_calculatedsane_question extends qtype_numerical_question 
{
	/**
	 * Initializes a new Scripted Numeric question by running the associated initialization 
	 * math-script.
	 *  
	 * 
	 * (non-PHPdoc)
	 * @see qtype_numerical_question::start_attempt()
	 */
	public function start_attempt(question_attempt_step $step, $variant) 
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
	
	/**
	 * Finds all variables names within a given HTML question text.
	 * 
	 * @param string $text The HTML question text to search for variable names.
	 */
	static function find_all_variables($text)
	{
		//extract all items of the form {[A-Za-z]+}, which are our variables
		$variables = preg_match_all("|\{([A-Za-z])+\}|", $text, $matches, PREG_SET_ORDER);
	
		//return the first element of each match- the variable name without the curly braces
		return array_map(function($arr) { return $arr[1];},  $matches);
	
	}
}
