#!/usr/bin/env php
<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
), $argc, $argv);

// Start parsing

require_once dirname(__FILE__).'/parseYacc.php';

$input = $argc > 1 ? $argv[1] : 'php://stdin';

$data = file_get_contents($input);
if ($data === FALSE) {
	fwrite(STDERR, "cannot read input\n");
	exit(1);
}

$grammar = parseYacc($data);
if ($grammar === FALSE) {
	fwrite(STDERR, "invalid input\n");
	exit(1);
}
?>
<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
			select, button { height: 30px; margin: 0px; padding: 0px; }
			button {width: 50px; }
			span {margin: 0px 10px 0px 0px;}
			span.token { color: red; text-transform: uppercase; }
			span.nonterminal { color: green; text-transform: lowercase; cursor: pointer; }
			span.nonterminal:hover {color: #8c8; text-decoration: underline; }
			#selectedNonterminalElement { font-weight: bold; text-transform: lowercase; }
		</style>
		<title>BYacc viewer</title>
	</head>
	<body>
		<div><select id="nonterminalListElement"></select><button>&#8656;</button><button>&#8658;</button></div>
		<hr/>
		<div style="margin-top: 30px">
			<div id="selectedNonterminalElement"></div>
			<div id="statementsElement"></div>
		</div>
		<script type="text/javascript">
			(function () {
				var grammar = <?=json_encode($grammar)?>;
				var nonterminalListElement = document.getElementById('nonterminalListElement');

				// var history = [];
				// var historyCursor = 0;

				var setNonterminalByClick = function() {
					if (nonterminalListElement.value != this.innerHTML) {
						nonterminalListElement.value = this.innerHTML;
						nonterminalListElement.onchange();
					}
				}

				var showNonterminal = function(nonterminal) {
					document.getElementById("selectedNonterminalElement").innerHTML = nonterminal;
					var statements = grammar['nonterminals'][nonterminal];
					var statementsElement = document.getElementById("statementsElement");
					statementsElement.innerHTML = "";
					for (var i in statements) {
						var statement = document.createElement("div");
						var terms = statements[i].split(/\s+/);
						for (var j in terms) {
							if (grammar['tokens'].indexOf(terms[j]) != -1) {
								var span = document.createElement("span");
								span.className = "token";
								span.innerHTML = terms[j];
								statement.appendChild(span);
							} else if (terms[j] in grammar['nonterminals']) {
								var span = document.createElement("span");
								span.className = "nonterminal";
								span.innerHTML = terms[j];
								span.onclick = setNonterminalByClick;
								statement.appendChild(span);
							} else {
								var span = document.createElement("s");
								span.innerHTML = terms[j];
								statement.appendChild(span);
							}
						}
						document.getElementById("statementsElement").appendChild(statement);
					}
				}

				nonterminalListElement.onchange = function() {
					showNonterminal(nonterminalListElement.value);
				};

				for (nonterminal in grammar['nonterminals']) {
					var option = document.createElement("option");
					option.innerHTML = nonterminal;
					option.value = nonterminal;
					if (nonterminal == grammar['start']) {
						option.selected="selected";
					}
					nonterminalListElement.appendChild(option);
				}

				nonterminalListElement.onchange();
			})();
		</script>
	</body>
</html>
