<?php

//don't display PHP errors just in case
ini_set('display_errors', 0);

if(empty($_POST['script']))
	die();

//include the math evaluator 
require_once 'evalmath.class.php';

//get the script to execute
$script = $_POST['script'];

//create a new math evaluator object
$m = new EvalMath();
$m->suppress_errors = true;

//evaluate the script passed on post
//(this _should_ be a safe operation- it's interpreted and heavily controlled by EvalMath, not php)
$errors = $m->evaluate_script($script);


//if we're not looking for errors related to a given target, display all errors
if(empty($_POST['target']))
{
	
	if($errors)
	{
		echo '<br />Errors exist within your code:<br />';
		echo '<ul>';
		foreach($errors as $error)
		{
			echo '<li>'; //TODO: use cfg?
			echo '&nbsp;&nbsp; <font color="#CC1B23"><strong>Syntax Error:</strong>&nbsp;&nbsp;'.$error.'</font>';
			echo '</li>';
		}
		echo '</ul>';
	}
	else
	{
		echo '<br /><table><tr><th style="padding: 5px; !important; border-bottom: 1px solid #000; border-right: 1px solid #000;">Variable</th><th style="padding: 55x; !important; border-bottom: 1px solid #000;">Sample Value</th></tr>';
		
		$vars = $m->vars();
		foreach($vars as $name => $var)		
			echo '<tr><td align="center" style="padding: 4px; !important; border-right: 1px solid #000;">'.$name.'</td><td align="center" style="padding: 4px; !important"><em>'.$var.'</em></td></tr>';
		
		echo '</table>';
	}
}
//otherwise, only display errors relevant to the given target
else
{
	//evaluate the target expression as well
	$m->evaluate($_POST['target']);
	
	
	if(!empty($m->last_error))
		echo $m->last_error; //TODO: remove line number, etc.
}
