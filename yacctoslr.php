<?php

$input = null;

if ($argc > 1) {
	$input = array_slice($argv, 1);
} else {
	$input = array('php://stdin');
}

require_once dirname(__FILE__).'/parseYacc.php';

foreach ($input as $filen) {
	$data = file_get_contents($filen);

	$grammar = parseYacc($data);
	if ($grammar === FALSE) {
		echo "invalid input";
		exit(1);
	}

	$nonterminalspattern = '/(?<=\s|^)(?:'.join('|', array_map('preg_quote',
		array_keys($grammar['nonterminals'])
	)).')(?=\s|$)/i';
	$tokenpattern = '/(?<=\s|^)(?:'.join('|', array_map('preg_quote',
		$grammar['tokens']
	)).')(?=\s|$)/i';

	foreach ($grammar['nonterminals'] as $nonterminal => $statements) {
		$statements = preg_replace_callback($tokenpattern, function ($matches) {
			return strtolower($matches[0]);
		}, $statements);

		$statements = preg_replace_callback($nonterminalspattern, function ($matches) {
			return strtoupper($matches[0]);
		}, $statements);

		$nonterminal = strtoupper($nonterminal);

		foreach ($statements as $statement) {
			if (empty($statement)) {
				$statement = "''";
			}
			printf("%s -> %s\n", $nonterminal, $statement);
		}

		echo "\n";
	}
}
