#!/usr/bin/env php
<?php

define("PARSE_GRAMMAR_RULE", 0);
define("PARSE_GRAMMAR_NONTERMINAL", 1);
define("PARSE_GRAMMAR_STATEMENT", 2);

function parseGrammar($data) {
	preg_match_all("/([\w]+'?) -> (.+)/", $data, $rules, PREG_SET_ORDER);
	for ($i = 0; $i < count($rules); $i++) {
		$rules[$i][PARSE_GRAMMAR_STATEMENT] = preg_split("/\s+/", $rules[$i][PARSE_GRAMMAR_STATEMENT]);
	}

	$nonterminals = array();
	$elements = array();
	foreach ($rules as $ruleNumber => $ruleArr) {
		array_push($nonterminals, $ruleArr[PARSE_GRAMMAR_NONTERMINAL]);
		$ruleArr[PARSE_GRAMMAR_STATEMENT];
		$elements = array_unique(array_merge($elements, $ruleArr[PARSE_GRAMMAR_STATEMENT]));
	}

	$tokens = array_diff($elements, $nonterminals);
	$nonterminals = array_unique($nonterminals);

	return array(
		'nonterminals' => $nonterminals,
		'tokens'       => $tokens,
		'rules'        => $rules
	);
}

if ($argv[0] === basename(__FILE__)) {
	$input = $argc > 1 ? $argv[1] : 'php://stdin';

	$data = file_get_contents($input);
	if ($data === FALSE) {
		fwrite(STDERR, "cannot read input\n");
		exit(1);
	}

	print_r(parseGrammar($data));

	echo "\n";
}
