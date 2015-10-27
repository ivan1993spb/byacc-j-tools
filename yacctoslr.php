<?php

require_once dirname(__FILE__).'/parseYacc.php';

$input = null;

if ($argc > 1) {
	$input = array_slice($argv, 1);
} else {
	$input = array('php://stdin');
}

foreach ($input as $filen) {
	$data = file_get_contents($filen);

	$grammar = parseYacc($data);
	if ($grammar === FALSE) {
		fwrite(STDERR, "invalid input\n");
		exit(1);
	}

	$nonterminalsPattern = '/(?<=\s|^)(?:'.join('|', array_map('preg_quote',
		array_keys($grammar['nonterminals'])
	)).')(?=\s|$)/i';

	$tokenPattern = '/(?<=\s|^)(?:'.join('|', array_map('preg_quote',
		$grammar['tokens']
	)).')(?=\s|$)/i';

	foreach ($grammar['nonterminals'] as $nonterminal => $statements) {
		$statements = preg_replace_callback($tokenPattern, function ($matches) {
			return strtolower($matches[0]);
		}, $statements);

		$statements = preg_replace_callback($nonterminalsPattern, function ($matches) {
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
