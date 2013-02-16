<?php

//Set up our Moodle session...
define('AJAX_SCRIPT', true);
require_once('../../../config.php');
require_once('locallib.php');

//Ensure that only logged in users can check scripts.
//(This allows us to limit the general usage.)
require_login();

//easy way to profile
function getTime() 
{ 
    $a = explode (' ',microtime()); 
    return(double) $a[0] + $a[1]; 
}

/**
 * Sends an error summary
 */
function send_errors($errors) {
    echo json_encode($errors)."\n";
}

/**
 * Creates a table summarizing the result of a script's execution.
 * 
 * @return A string containing the html for the created table.
 */
function summarize_execution($variables) {

    $html = '';

    $html = html_writer::start_tag('div', array('class' => 'code-result'));
    
    //Create a new HTML table, which will store each of the key-value mappings.
    $table = new html_table;
    $table->attributes = array('class' => 'code-result');
    $table->head = array('Variable', 'Sample Value'); // TODO: internationalize

    //Fill in each of the name -> value variable mappings.
    $table->data = array();
    foreach($variables as $name => $value) {
        $table->data[] = array(htmlentities($name), htmlentities($value));
    }

    //Render the table, close the containing div, and return the code for the summary.
    $html .= html_writer::table($table);
    $html .= html_writer::end_tag('div');
    return $html;
}

//don't display PHP errors, just in case
ini_set('display_errors', 1);

if(empty($_POST['script'])) {
	die();
}

//get the script to execute
$script = $_POST['script'];

//create a new sandboxed script evaluator
$interpreter = qtype_scripted_language_manager::create_interpreter($_POST['language']);

$error = null;

//record the time at the start of the script's execution
$start_time = getTime();
  
try {
    //Evaluate the given script in a safe evaluation sandbox.
    $interpreter->execute($script);
}
//If an error occurs during execution, capture it.
catch(Exception $x) {
    $error = $interpreter->error_information($x);
}

//and record the time afterwards
$end_time = getTime();

//if we're not looking for errors related to a given target, display all errors
if(empty($_POST['target']))
{
    //Send a summary of any errors which (may have) occurred during
    //evaluation of the user script.
    send_errors($error);

    //If no error occurred, 
    if(!$error)
    {
        $variables = $interpreter->summarize_variables(); 
        echo summarize_execution($interpreter->summarize_variables());
	}
}
//otherwise, only display errors relevant to the given target
//TODO?
else
{
  //evaluate the target expression as well
  try
  {
    $interpreter->evaluate($_POST['target']);
  } 
  catch(Exception $e) {
    print_object($e);
  }
}

//Print a message summarizing the execution time for the given script.
//This allows a primitive form of benchmarking.
$exec_time = number_format($end_time - $start_time, 3);
echo html_writer::tag('span',  get_string('executiontime', 'qtype_scripted', $exec_time), array('class' => 'footnote'));

