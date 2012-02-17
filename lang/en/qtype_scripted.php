<?php
/**
* Localization strings for the Scripted quesiton type.
*
* @package    qtype
* @subpackage scripted
* @copyright  2011 Binghamton University
* @author	   Kyle Temkin <ktemkin@binghamton.edu>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

$string['addmoreanswerblanks'] = 'Blanks for {no} More Answers';
$string['answer'] = 'Answer: {$a}';
$string['answermustbegiven'] = 'You must enter an answer if there is a grade or feedback.';
$string['answerno'] = 'Graded Expression {$a}';
$string['caseno'] = 'No, case is unimportant';
$string['casesensitive'] = 'Case sensitivity';
$string['caseyes'] = 'Yes, case must match';
$string['correctansweris'] = 'For your unique values, a correct answer is: {$a}.';
$string['correctanswers'] = 'Correct answers';
$string['filloutoneanswer'] = 'You must provide at least one possible answer. Answers left blank will not be used. \'*\' can be used as a wildcard to match any characters. The first matching answer will be used to determine the score and feedback.';
$string['notenoughanswers'] = 'This type of question requires at least {$a} answers';
$string['pleaseenterananswer'] = 'Please enter an answer.';


$string['pluginname'] = 'Scripted';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent types a number, word or short phrase. There may be several possible correct answers, each with a different grade; the user is graded according to a (typically mathematical) script.';
$string['pluginname_link'] = 'question/type/scripted';
$string['pluginnameadding'] = 'Adding a scripted question';
$string['pluginnameediting'] = 'Editing a scripted question';
$string['pluginnamesummary'] = 'Allows a response of one or a few words that is graded by comparing against various model answers, which are generated by a script, and may be randomized.';


$string['addmoreanswerblanks'] = 'Blanks for {no} More Answers';
$string['answermustbegiven'] = 'You must enter an answer if there is a grade or feedback.';
$string['answerno'] = 'Graded Expression {$a}';
$string['filloutoneanswer'] = 'You must provide at least one possible expression. Expressions left blank will not be used. The first matching expression will be used to determine the score and feedback.';


$string['initscript'] = 'Initialization Script';
$string['options'] = 'Question Parameters';

//response modes
$string['responseform'] = 'Require response to be:';
$string['resp_string'] = 'STRING (LOWERCASE): A string, which may be numeric, if matching mode is selected, and will be converted to lowercase.';
$string['resp_string_case'] = 'STRING (CASE SENSITIVE): A string, which may be numeric, if matching mode is selected.';
$string['resp_numeric'] = 'DECIMAL: A base-ten number, in any format recognized by PHP.';
$string['resp_hexadecimal'] = 'HEXADECIMAL: A hexadecimal integer, which will be converted to base-10 before checking.';
$string['resp_binary'] = 'BINARY: A binary number, which will be converted to base-10 before checking.';
$string['resp_octal'] = 'OCTAL: An octal number, which will be converted to base-10 before checking.';

//invalid response warnings
$string['invalid_numeric'] = '<b>Your response could not be interpreted as a number.</b><br/> Please make sure your answer is a valid base-ten number, then try again.';
$string['invalid_binary'] = '<b>Your response could not be interpreted as a binary number.</b> <br/>Make sure your answer is valid, then try again.';
$string['invalid_hexadecimal'] = '<b>Your response could not be interpreted as a hexadecimal number.</b><br/> Make sure your answer is valid, then try again.';
$string['invalid_octal'] = '<b>Your response could not be interpreted as an octal number.</b> <br/>Make sure your answer is valid, then try again.';
$string['invalid_string'] = '<b>Please enter an answer.</b> <br/>You cannot check your answer until you have provided a valid response.';


//evaluation modes
$string['answerform'] = 'Evaluation Mode';
$string['eval_direct'] = 'User\'s answer must match the result of the given expression.';
$string['eval_congruent'] = 'User\'s answer must be congruent to the result of the given expression.';  
$string['eval_boolean'] = 'The given expression must evaluate to true.';

