<?php

require_once dirname(__FILE__).'/parseArgs.php';
require_once dirname(__FILE__).'/parseYacc.php';

$settings = parseArgs(array(
	'save_token_value' => '' // tokens which values should be saved
), $argc, $argv);

if (!empty($settings['save_token_value'])) {
	$settings['save_token_value'] = explode(',', $settings['save_token_value']);
}

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

foreach ($grammar['nonterminals'] as $nonterminal => $statements) {

	echo $nonterminal."\n";

	$maxlength = max(array_map('strlen', $statements));

	foreach ($statements as $index => $statement) {
		echo "\t".($index === 0 ? ':' : '|')." ";
		printf("%-".$maxlength."s", $statement);

		// Generate java code

		$args = array();
		if (!empty($statement)) {
			$ss = preg_split("/\s+/", $statement);
			foreach ($ss as $i => $s) {
				if (array_key_exists($s, $grammar['nonterminals'])) {
					array_push($args, '(PTElement)$'.($i+1).'.obj');
				} else {
					$leafArgs = 'Parser.'.$s;
					if (in_array($s, $settings['save_token_value'])) {
						$leafArgs .= ', ';
						$leafArgs .= '$'.($i+1).".sval";
					}
					array_push($args, 'new PTLeaf('.$leafArgs.')');
				}
			}
		}

		echo ' ';

		// get java const name
		$const = strtoupper($nonterminal);

		if ($grammar['start'] == $nonterminal) {
			printf('{ $$ = new ParserVal(new ParsingTree(new PTNode(Nonterminals.%s, %s))); }', $const, join(', ', $args));
		} else {
			printf('{ $$ = new ParserVal(new PTNode(Nonterminals.%s, %s)); }', $const, join(', ', $args));
		}

		echo "\n";
	}

	echo "\t;\n";
	echo "\n";
}

echo "%%\n";

echo $yaccFileParts[2];
