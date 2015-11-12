<?php

require_once dirname(__FILE__).'/parseGrammar.php';

$input = $argc > 1 ? $argv[1] : 'php://stdin';

$data = file_get_contents($input);
if ($data === FALSE) {
	fwrite(STDERR, "cannot read input\n");
	exit(1);
}

$grammar = parseGrammar($data);
if ($grammar === FALSE) {
	fwrite(STDERR, "invalid input\n");
	exit(1);
}

$indent = strlen(strval(count($grammar))) + 4;

echo "input:\n";
foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	printf("%-".$indent."d%s\n", $ruleNumber, $ruleArr[PARSE_GRAMMAR_RULE]);
}

return;
echo "oblow:\n";

$elements = array(array('^', 0));
foreach ($grammar as $ruleNumber => $ruleArr) {
	foreach ($ruleArr[PARSE_GRAMMAR_STATEMENT] as $element) {
		array_push($elements, array($element, $ruleNumber));
	}
}

// define("ELEMENT", 0);
// define("RULE_NUMBER", 1);

// foreach ($grammar as $ruleNumber => $ruleArr) {
// 	foreach ($ruleArr[PARSE_GRAMMAR_STATEMENT] as $element) {
// 		array_push($elements, array($element, $ruleNumber));
// 	}
// }

echo "\n";

/*

епсилон E = EOF
Допуск достигли корня

*/
