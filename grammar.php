<?php

$input = null;

if ($argc > 1) {
	$input = array_slice($argv, 1);
} else {
	$input = array('php://stdin');
}

foreach ($input as $filen) {
	$data = file_get_contents($filen);

	$grammar = parseGrammar($data);
	if ($grammar === FALSE) {
		echo "invalid input";
		exit(1);
	}

	$lexspattern = '/(?:^|$|\s+)'.join('(?:^|$|\s+)|', array_keys($grammar['lexs'])).'(?:^|$|\s+)/i';
	$tokenpattern = '/(?:^|$|\s+)'.join('(?:^|$|\s+)|', $grammar['tokens']).'(?:^|$|\s+)/i';

	foreach ($grammar['lexs'] as $lex => $statements) {
		$statements = preg_replace_callback($tokenpattern, function ($matches) {
			return strtolower($matches[0]);
		}, $statements);

		$statements = preg_replace_callback($lexspattern, function ($matches) {
			return strtoupper($matches[0]);
		}, $statements);

		$lex = strtoupper($lex);

		foreach ($statements as $statement) {
			if (empty($statement)) {
				$statement = "''";
			}
			printf("%s -> %s\n", $lex, $statement);
		}
		echo "\n";
	}
}

function parseGrammar($grammar) {
	// grammar file has 3 parts
	$parts = explode("\n%%\n", $grammar, 3);
	if (sizeof($parts) !== 3) {
		return FALSE;
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

	$lexs = array();
	if (preg_match_all('/([a-z_]+)\s*:\s*((?:.|\s)*?)?\s*;/i', $parts[1], $matches, PREG_SET_ORDER) !== FALSE) {
		foreach ($matches as $m) {
			$lexs[$m[1]] = preg_split("/\s*\|\s*/", $m[2]);
		}
	} else {
		return FALSE;
	}

	return array(
		'tokens' => $tokens,
		'lexs'   => $lexs
	);
}
