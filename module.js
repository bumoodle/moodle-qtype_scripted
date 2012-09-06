M.qtype_scripted = {}
M.qtype_scripted.check_timer = null;
M.qtype_scripted.check_uri =  M.cfg.wwwroot + '/question/type/scripted/check_errors.php';

M.qtype_scripted.reset_timeout = function() {
	if(M.qtype_scripted.check_timer) {
		clearTimeout(M.qtype_scripted.check_timer);
    }
	M.qtype_scripted.check_timer = setTimeout('M.qtype_scripted.run_check();', 500);
};

M.qtype_scripted.run_check = function () {
	var script = encodeURIComponent(M.qtype_scripted.code.getValue());
	YAHOO.util.Connect.asyncRequest('POST', this.check_uri, this.check_callback, 'script=' + script);
};

M.qtype_scripted.display_results = function(resp) {
	YUI().use("node", function(Y) { Y.one("#dynamicerrors").setContent(resp.responseText); } );
};

M.qtype_scripted.init_dynamic = function(Y, id, editormode) {
    var options = { lineNumbers: true, mode: editormode, theme: "elegant", onKeyEvent: M.qtype_scripted.reset_timeout };
    M.qtype_scripted.code = CodeMirror.fromTextArea(Y.DOM.byId(id), options);
    M.qtype_scripted.run_check();
};

M.qtype_scripted.init_static = function(Y, id, editormode) {
    var options = { lineNumbers: true, mode: editormode, theme: "elegant", onKeyEvent: M.qtype_scripted.reset_timeout };
    CodeMirror.fromTextArea(Y.DOM.byId(id), options); 
};

M.qtype_scripted.check_callback = {};
M.qtype_scripted.check_callback.success= M.qtype_scripted.display_result;

