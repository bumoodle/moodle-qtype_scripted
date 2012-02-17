
var checkTimer;

checkCallback =
	{
		success: displayResults
	}

function resetTimeout()
{
	if(checkTimer)
		clearTimeout(checkTimer);
		
	checkTimer = setTimeout('runCheck();', 500);
}

function runCheck()
{
	var script = encodeURIComponent(code.getValue());
	
	YAHOO.util.Connect.asyncRequest('POST', checkURI, checkCallback, 'script=' + script);
}

function displayResults(resp)
{
	YUI().use("node", function(Y) { Y.one("#dynamicerrors").setContent(resp.responseText); } );
}


YAHOO.util.Event.onDOMReady(runCheck);