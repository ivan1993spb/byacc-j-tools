#!/usr/bin/env php
<?php

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

$nonterminalsPattern = '/(?<=\s|^)(?:'.join('|', array_map('preg_quote',
	array_keys($grammar['nonterminals'])
)).')(?=\s|$)/i';

$tokenPattern = '/(?<=\s|^)(?:'.join('|', array_map('preg_quote',
	$grammar['tokens']
)).')(?=\s|$)/i';

// $i = 0;
// unset($grammar['nonterminals']['root']);
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
		// printf("%-4d %s -> %s\n", ++$i, $nonterminal, $statement);
		// TODO ROOT'
		printf("%s -> %s\n", $nonterminal, $statement);
	}

	echo "\n";
}
