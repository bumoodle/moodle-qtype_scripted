<?php


defined('MOODLE_INTERNAL') || die();


/**
* Defines the editing form for the scripted question type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class qtype_scripted_renderer extends qtype_renderer 
{
	/**
	 * Prints the question, and all relevant entry fields. 
	 * 
	 * @see qtype_renderer::formulation_and_controls()
	 */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) 
    {

    	//get the question and the current answer
        $question = $qa->get_question();
        $currentanswer = $qa->get_last_qt_var('answer');

        $inputname = $qa->get_qt_field_name('answer');
        
        $inputattributes = 
	        array
	        (
	            'type' => 'text',
	            'name' => $inputname,
	            'value' => $currentanswer,
	            'id' => $inputname,
	            'size' => 80,
	        );

        //if the question is read-only, set the input field to be read-only
        if ($options->readonly) 
            $inputattributes['readonly'] = 'readonly';

        $feedbackimg = '';
        
        
        if ($options->correctness) 
        {
            $answer = $question->get_matching_answer(array('answer' => $currentanswer));
            if ($answer) 
            {
                $fraction = $answer->fraction;
            } 
            else 
            {
                $fraction = 0;
            }
            $inputattributes['class'] = $this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        //get the question text from the question attempt
        $questiontext = $question->format_questiontext($qa);
        $placeholder = false;
        
        
        
        
        if (preg_match('/_____+/', $questiontext, $matches)) 
        {
            $placeholder = $matches[0];
            $inputattributes['size'] = round(strlen($placeholder) * 1.1);
        }

        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;

        if ($placeholder) 
            $questiontext = substr_replace($questiontext, $input, strpos($questiontext, $placeholder), strlen($placeholder));

        
        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if (!$placeholder) 
        {
            $result .= html_writer::start_tag('div', array('class' => 'ablock'));
            $result .= get_string('answer', 'qtype_shortanswer', html_writer::tag('div', $input, array('class' => 'answer')));
            $result .= html_writer::end_tag('div');
        }

        if ($qa->get_state() == question_state::$invalid) 
            $result .= html_writer::nonempty_tag('div', $question->get_validation_error(array('answer' => $currentanswer)), array('class' => 'validationerror'));

        return $result;
    }

    public function specific_feedback(question_attempt $qa) 
    {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer(array('answer' => $qa->get_last_qt_var('answer')));
        if (!$answer || !$answer->feedback) {
            return '';
        }

        //replace any variables in the feedback with their values
        $feedback = $question->fill_in_variables($answer->feedback);

        return $question->format_text($feedback, $answer->feedbackformat, $qa, 'question', 'answerfeedback', $answer->id);
    }

    public function correct_response(question_attempt $qa) 
    {
    	//get a reference to the quesiton object
    	$question = $qa->get_question();
    	
    	//get the correct response for the given question
    	$correct_response = $question->get_correct_response();
    	$answer = $correct_response['answer'];

		//and return a 
        return get_string('correctansweris', 'qtype_scripted', s($answer));
    }
    

}
