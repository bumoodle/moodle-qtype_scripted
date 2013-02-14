//Initialize the "qtype_scripted" JS module.
M.qtype_scripted = {}

// Start off with no "check" timer running.
M.qtype_scripted.check_timer = null;

// Start off with no known language.
M.qtype_scripted.language = '';

// Start off with an empty array of error markers.
M.qtype_scripted.error_markers = [];

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
		clearTimeout(M.qtype_scripted.check_timer)
  }

  // Create a new timer which will execute the syntax checker if no input is recieved for the next "check delay".
	M.qtype_scripted.check_timer = setTimeout(M.qtype_scripted.run_check, M.qtype_scripted.check_delay);

};

/**
 *  This call-back displays the results of a query to the syntax checker.
 *  resp: The response object from the syntax checker; which is automatically used to populate the "response text" div.
 */
M.qtype_scripted.display_results = function(id, resp, args) {

    var response = resp.responseText;

    //Extract any error information presented from the response text.
    var error_info = JSON.parse(response.substr(0, response.indexOf("\n")));

    //And extract the content of the page.
    var page_content = response.substr(response.indexOf("\n"));

    //If we were provided with error information, display it.
    if(error_info) {
      M.qtype_scripted.mark_error(error_info.line_number, error_info.message);
    } 
    //Otherwise, clear all errors.
    else {
      M.qtype_scripted.clear_errors();
    }

    // Set the value of the "dynamic errors" div to match the response from the syntax checker.
    // TODO: Adjust this to allow multiple dynamic editors per page?
	  Y.one("#dynamicerrors").setContent(page_content);
};

/**
 *  Runs a syntax check and displays a summary of any errors encountered, and a quick set of sample question values.
 */
M.qtype_scripted.run_check = function () {

    // Encode the user-script in a format that can be sent to our safe evaluator.
	  var script = encodeURIComponent(M.qtype_scripted.code.getValue());

    // Create a "callback" object which will automatically display the results after the AJAX query is complete.
    var cfg = {
        method: 'POST',
        data: 'language=' + M.qtype_scripted.language +  '&script=' + script,
        on: { complete: M.qtype_scripted.display_results }
    };

    console.log(cfg);

    // Request that the safe evaluator run our code, and display the results.
  	Y.io(M.qtype_scripted.check_uri, cfg);
};

/**
 * Mark the given line as containing an error.
 */
M.qtype_scripted.mark_error = function(line_number, message, keep) {

  if(!keep) {
    M.qtype_scripted.clear_errors();
  }

  //Create the error bar.
  var msg = document.createElement("div");
  var icon = msg.appendChild(document.createElement("span"));
  icon.innerHTML = " !";
  icon.className = "code-error-icon";
  msg.appendChild(document.createTextNode(message));
  msg.className = "code-error";

  var marker = M.qtype_scripted.code.addLineWidget(line_number - 1, msg, {coverGutter: true, noHScroll: true});
  M.qtype_scripted.error_markers.push(marker);
}

M.qtype_scripted.clear_errors = function() {
    for(var i = 0; i < M.qtype_scripted.error_markers.length; ++i) {
      M.qtype_scripted.error_markers[i].clear();
    } 

    M.qtype_scripted.error_markers = [];
}

/**
 *  Initialize a syntax-highlighted editor with dynamic syntax checking.
 */
M.qtype_scripted.init_dynamic = function(Y, id, editormode, language) {

    // Create the basic formatting options for our CodeMirror editor.
    var options = { lineNumbers: true, mode: editormode, theme: "neat", onKeyEvent: M.qtype_scripted.reset_timeout };

    //Store the active language.
    M.qtype_scripted.language = language;

    // Wrap the given text-area with a CodeMirror instance.
    // TODO: allow more than one dynamic editor per page. (Assoc. array of editors with div id as key?)
    M.qtype_scripted.code = CodeMirror.fromTextArea(document.getElementById(id), options);

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
    CodeMirror.fromTextArea(document.getElementById(id), options); 
};


