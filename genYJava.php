<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
	'package'   => '',       // target package
	'tokens'    => ''        // generate java classes for passed tokens
), $argc, $argv);

$settings['tokens'] = explode(',', $settings['tokens']);

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

echo "\n";

echo "%{\n";
if (!empty($settings['package'])){
	echo "  import ".$settings['package'].";\n";
}
echo "%}\n";
echo "\n";

foreach (explode("\n", wordwrap(join(' ', $grammar['tokens']))) as $tokens) {
	echo "%token ".$tokens."\n";
}
echo "\n";

echo "%start ".$grammar['start']."\n";
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
				if (in_array($s, $nonterminals) || in_array($s, $settings['tokens'])) {
					array_push($args, '$'.($i+1));
				}
			}
		}

		echo ' ';

		printf("{ $$ = new %s(%s); }", $className, join(', ', $args));

		echo "\n";
	}

	echo "\t;\n";
	echo "\n";
}

echo "\n";

echo "%%\n";

echo "\n";

echo "// nothing...\n";

echo "\n";

function labelToClassName($label) {
	return preg_replace_callback('/(?:^|_)([a-z0-9])/', function ($matches) {
		return strtoupper($matches[1]);
	}, trim(strtolower($label)));
}
