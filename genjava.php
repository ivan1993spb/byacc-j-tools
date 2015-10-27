<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
	'package'   => '',       // target package
	'directory' => getcwd(), // target directory
	'parent'    => '',       // parent class for Token and Nonterminal classes
	'tokens'    => ''        // generate java classes for passed tokens
), $argc, $argv);

if (is_dir($settings['directory'])) {
	if (!($files = @scandir($directory)) || count($files) > 2) {
		echo "target directory is not empty\n";
		exit(1);
	}
} elseif (!mkdir($directory, 0777, true)) {
	echo "cannot create directory $directory\n";
	exit(1);
}

$input = null;
if ($argc > 1) {
	$input = array_slice($argv, 1);
} else {
	$input = array('php://stdin');
}

require_once dirname(__FILE__).'/parseYacc.php';

$classpattern = <<<EOT
package %s;

class %s extends ParserVal {
%s
}

EOT;

$constructorpattern = <<<EOT

	public %s(%s) {

	}

EOT;

foreach ($input as $filen) {
	$data = file_get_contents($filen);

	$grammar = parseYacc($data);
	if ($grammar === FALSE) {
		echo "invalid input";
		exit(1);
	}

	$nonterminals = array_keys($grammar['nonterminals']);

	foreach ($grammar['nonterminals'] as $nonterminal => $statements) {
		// For each nonterminal create class
		$className = nonterminalToClassName($nonterminal);
		$constructors = array();

		foreach ($statements as $statement) {

			// For each statement create constructor
			$args = array();

			if (!empty($statement)) {
				$ss = preg_split("/\s+/", $statement);
				foreach ($ss as $s) {
					if (in_array($s, $nonterminals)) {
						array_push($args, nonterminalToClassName($s).' '.nonterminalToVarName($s));
					}
				}
			}

			array_push($constructors, sprintf($constructorpattern, $className, join(', ', $args)));
		}

		$constructors = array_unique($constructors);
		$src = sprintf($classpattern, $package, $className, join("\n", $constructors));

		if (file_put_contents($directory.DIRECTORY_SEPARATOR.$className.'.java', $src) === FALSE) {
			echo "cannot write class $className\n";
			exit(1);
		}
	}
}

function nonterminalToClassName($nonterminal) {
	return preg_replace_callback('/(?:^|_)([a-z0-9])/', function ($matches) {
		return strtoupper($matches[1]);
	}, trim(strtolower($nonterminal)));
}

function nonterminalToVarName($nonterminal) {
	return preg_replace_callback('/_([a-z0-9])/', function ($matches) {
		return strtoupper($matches[1]);
	}, trim(strtolower($nonterminal)));
}
