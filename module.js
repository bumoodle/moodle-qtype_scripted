//Initialize the "qtype_scripted" JS module.
M.qtype_scripted = {}

// Start off with no "check" timer running.
M.qtype_scripted.check_timer = null;

// Specify a default URL to use for error-checking.
M.qtype_scripted.check_uri =  M.cfg.wwwroot + '/question/type/scripted/check_errors.php';

/**
 *  The "check delay" determines how long the editor should wait after a user change to check the user's code for
 *  syntax errors.
 */
M.qtype_scripted.check_delay = 500;

/**
 *  Resets the internal key-press timeout, delaying the "syntax check" for at least another "check delay" period.
 */
M.qtype_scripted.reset_timeout = function() {

    // If a timer is currently measuring the time since the last key-press, cancel it.
	if (M.qtype_scripted.check_timer) {
		clearTimeout(M.qtype_scripted.check_timer);
    }

    // Create a new timer which will execute the syntax checker if no input is recieved for the next "check delay".
	M.qtype_scripted.check_timer = setTimeout('M.qtype_scripted.run_check();', M.qtype_scripted.check_delay);
};

/**
 *  This call-back displays the results of a query to the syntax checker.
 *  resp: The response object from the syntax checker; which is automatically used to populate the "response text" div.
 */
M.qtype_scripted.display_results = function(resp) {

    // Set the value of the "dynamic errors" div to match the response from the syntax checker.
    // TODO: Adjust this to allow multiple dynamic editors per page?
	YUI().use("node", function(Y) { Y.one("#dynamicerrors").setContent(resp.responseText); } );
};

/**
 *  Runs a syntax check and displays a summary of any errors encountered, and a quick set of sample question values.
 */
M.qtype_scripted.run_check = function () {

    // Encode the user-script in a format that can be sent to our safe evaluator.
	var script = encodeURIComponent(M.qtype_scripted.code.getValue());

    // Create a "callback" object which will automatically display the results after the AJAX query is complete.
    var check_callback = {
        success: M.qtype_scripted.display_results
    };

    // Request that the safe evaluator run our code, and display the results.
	YAHOO.util.Connect.asyncRequest('POST', M.qtype_scripted.check_uri, check_callback, 'script=' + script);
};


/**
 *  Initialize a syntax-highlighted editor with dynamic syntax checking.
 */
M.qtype_scripted.init_dynamic = function(Y, id, editormode) {

    // Create the basic formatting options for our CodeMirror editor.
    var options = { lineNumbers: true, mode: editormode, theme: "elegant", onKeyEvent: M.qtype_scripted.reset_timeout };

    // Wrap the given text-area with a CodeMirror instance.
    // TODO: allow more than one dynamic editor per page. (Assoc. array of editors with div id as key?)
    M.qtype_scripted.code = CodeMirror.fromTextArea(Y.DOM.byId(id), options);

    // Run an initial syntax check, so the user doesn't have to press a key to get their initial sample values.
    M.qtype_scripted.run_check();
};

/**
 *  Initialize a static syntax highlighter, without dynamic syntax checking.
 */
M.qtype_scripted.init_static = function(Y, id, editormode) {
    
    // Create the basic formatting options for our CodeMirror editor.
    var options = { lineNumbers: true, mode: editormode, theme: "elegant", onKeyEvent: M.qtype_scripted.reset_timeout };

    // Wrap the textarea with the given ID with a CodeMirror edtior.
    CodeMirror.fromTextArea(Y.DOM.byId(id), options); 
};


