<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
	'package'    => '',
), $argc, $argv);

if (empty($settings['package'])) {
	fwrite(STDERR, "package cannot be empty\n");
	fwrite(STDERR, "use --package=org.some.pack.age\n");
	exit(1);
}

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

printf("package %s;", $settings['package']);

echo "\n\n";

echo "public enum Nonterminal {\n";

$nonterminals = array_map(function($s){
	return '    '.strtoupper($s);
}, array_keys($grammar['nonterminals']));

echo join(",\n", $nonterminals);

echo "\n}\n";
