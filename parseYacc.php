<?php

function parseYacc($grammar) {
	// grammar file has 3 parts
	$parts = explode("\n%%\n", $grammar, 3);
	if (sizeof($parts) !== 3) {
		return FALSE;
	}

	// get start point
	$start = null;
	if (preg_match("/%start ([a-z_]+)/i", $parts[0], $matches) === 1) {
		$start = $matches[1];
	}

	// get tokens
	$tokens = array();
	if (preg_match_all("/^%token\s+([a-z_ ]+)/im", $parts[0], $matches) > 0) {
		foreach ($matches[1] as $tokenstr) {
			$tokens = array_merge($tokens, array_diff(preg_split("/\s+/", $tokenstr), $tokens));
		}
	}
	$tokens = array_map('trim', $tokens);

	// remove src code
	$parts[1] = preg_replace("/{(?:.|\s)*?}/", "", $parts[1]);

	// remove comments
	$parts[1] = preg_replace("{/\*(?:.|\s)*?\*/}", "", $parts[1]);

	$nonterminals = array();
	if (preg_match_all('/([a-z_.]+)\s*:\s*((?:.|\s)*?)\s*;/i', $parts[1], $matches, PREG_SET_ORDER) !== FALSE) {
		foreach ($matches as $m) {
			$nonterminals[$m[1]] = preg_split("/\s*\|\s*/", $m[2]);
		}
	} else {
		return FALSE;
	}

	return array(
		'start'        => $start,
		'tokens'       => $tokens,
		'nonterminals' => $nonterminals
	);
}

if ($argv[0] === basename(__FILE__)) {
	if ($argc > 1) {
		$input = array_slice($argv, 1);
	} else {
		$input = array('php://stdin');
	}

	foreach ($input as $filen) {
		$data = file_get_contents($filen);
		var_dump(parseYacc($data));
	}

	echo "\n";
}
