<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
	'save_token_value' => ''
), $argc, $argv);

$settings['save_token_value'] = explode(',', $settings['save_token_value']);

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

// Save first and third parts of yacc file and rewrite second part
$yaccFileParts = explode("\n%%\n", $data, 3);
if (sizeof($yaccFileParts) !== 3) {
	fwrite(STDERR, "invalid input\n");
	exit(1);
}

echo $yaccFileParts[0];

echo "\n";

echo "%%\n";

echo "\n";

$nonterminals = array_keys($grammar['nonterminals']);

foreach ($grammar['nonterminals'] as $nonterminal => $statements) {

	echo $nonterminal."\n";

	$className = labelToClassName($nonterminal);

	$maxlength = max(array_map('strlen', $statements));

	foreach ($statements as $index => $statement) {
		if ($index === 0) {
			echo "\t: ";
		} else {
			echo "\t| ";
		}

		printf("%-".$maxlength."s", $statement);

		// Generate java code

		$args = array();
		if (!empty($statement)) {
			$ss = preg_split("/\s+/", $statement);
			foreach ($ss as $i => $s) {
				if (in_array($s, $nonterminals)) {
					array_push($args, '(PTElement)$'.($i+1).'.obj');
				} else {
					$value = in_array($s, $settings['save_token_value']) ? '$'.($i+1).".sval" : "null";
					array_push($args, 'new PTLeaf(Parser.'.$s.', '.$value.')');
				}
			}
		}

		echo ' ';

		if ($grammar['start'] == $nonterminal) {
			printf('{ $$ = new ParserVal(new ParsingTree(new PTNode(Nonterminals.%s, %s))); }', strtoupper($nonterminal), join(', ', $args));
		} else {
			printf('{ $$ = new ParserVal(new PTNode(Nonterminals.%s, %s)); }', strtoupper($nonterminal), join(', ', $args));
		}

		echo "\n";
	}

	echo "\t;\n";
	echo "\n";
}

echo "%%\n";

echo $yaccFileParts[2];

function labelToClassName($label) {
	return preg_replace_callback('/(?:^|_)([a-z0-9])/', function ($matches) {
		return strtoupper($matches[1]);
	}, trim(strtolower($label)));
}
