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

//record the start time
$start = getTime();

//don't display PHP errors just in case
ini_set('display_errors', 0);

if(empty($_POST['script'])) {
	die();
}

//get the script to execute
$script = $_POST['script'];

//create a new math evaluator object
$interpreter = qtype_scripted_language_manager::create_interpreter($_POST['language']);

//evaluate the script passed on post
//(this _should_ be a safe operation- it's interpreted and heavily controlled by EvalMath, not php)
$error = null;
  
try {
  $interpreter->execute($script);
}
catch(Exception $x) {
  $error = $interpreter->error_information($x);
}

//if we're not looking for errors related to a given target, display all errors
if(empty($_POST['target']))
{
	
	if($error)
  {
    echo json_encode($error)."\n";
		echo '<br />Errors exist within your code:<br />';
		echo '<ul>';
    echo '<li>'; //TODO: use cfg?
    echo '&nbsp;&nbsp; <font color="#CC1B23"><strong>Syntax Error:</strong>&nbsp;&nbsp;'.$error['message'].'</font>';
    echo '</li>';
    echo '</ul>';
	}
	else
	{
    echo json_encode(null)."\n";
		echo '<br /><table><tr><th style="padding: 5px; !important; border-bottom: 1px solid #000; border-right: 1px solid #000;">Variable</th><th style="padding: 55x; !important; border-bottom: 1px solid #000;">Sample Value</th></tr>';
		
		$vars = $interpreter->get_variables();
		foreach($vars as $name => $var)		
			echo '<tr><td align="center" style="padding: 4px; !important; border-right: 1px solid #000;">'.$name.'</td><td align="center" style="padding: 4px; !important"><em>'.print_r($var, 1).'</em></td></tr>'; 
		echo '</table>';
	}
}
//otherwise, only display errors relevant to the given target
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

$end = getTime();

echo '<small><em>Script took '.number_format($end - $start, 2).' seconds to execute.</em></small>';

//DEBUG
//echo '<pre>'.print_r($m->vars(), 1).'</pre>';
//echo '<pre>'.print_r($m, 1).'</pre>';
