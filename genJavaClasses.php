<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
	'package'   => '',       // target package
	'directory' => getcwd(), // target directory
	'parent'    => '',       // parent class for Token and Nonterminal classes
	'tokens'    => ''        // generate java classes for passed tokens
), $argc, $argv);

// Checking arguments

if (empty($settings['package'])) {
	fwrite(STDERR, "package cannot be empty\n");
	fwrite(STDERR, "use --package=org.some.pack.age\n");
	exit(1);
}

if (empty($settings['directory'])) {
	fwrite(STDERR, "directory cannot be empty\n");
	exit(1);
}

if (empty($settings['parent'])) {
	fwrite(STDERR, "please specify parent class package and name:\n");
	fwrite(STDERR, "use --parent=org.some.pack.age.ParentClass\n");
	exit(1);
}

if (!is_dir($settings['directory']) && !mkdir($settings['directory'], 0777, true)) {
	fprintf(STDERR, "cannot create directory %s\n", $settings['directory']);
	exit(1);
}

$settings['parent_class_name'] = getClassName($settings['parent']);
$settings['tokens'] = explode(',', $settings['tokens']);

// Create patterns

// Parent class for all nonterminal classes
$nonterminalFilePattern = <<<EOT
package %s;

import %s;

public abstract class Nonterminal extends %s {}

EOT;

// Parent class for all token classes
$tokenFilePattern = <<<EOT
package %s;

import %s;

public abstract class Token extends %s {

	public Token(%s %s) {
		// ...
	}

}

EOT;

// Nonterminal class pattern
$classNonterminalPattern = <<<EOT
package %s;

public class %s extends Nonterminal {
%s
}

EOT;

// Token class pattern
$classTokenPattern = <<<EOT
package %s;

public class %s extends Token {}

EOT;

// Patterns for class constructor
$constructorPattern = <<<EOT

	public %s(%s) {
		// ...
	}

EOT;

// Create parent classes

// Create parent class for nonterminal classes
createClassFile(
	$settings['directory'].DIRECTORY_SEPARATOR.'Nonterminal.java',
	sprintf($nonterminalFilePattern, $settings['package'], $settings['parent'], $settings['parent_class_name'])
);

if (!empty($settings['tokens'])) {
	// Create parent class for token classes
	createClassFile(
		$settings['directory'].DIRECTORY_SEPARATOR.'Token.java',
		sprintf($tokenFilePattern, $settings['package'], $settings['parent'], $settings['parent_class_name'],
			$settings['parent_class_name'], lcfirst($settings['parent_class_name']))
	);
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

// Generate token files
// foreach (array_intersect($grammar['tokens'], $settings['tokens']) as $token) {
// 	$tokenClassName = labelToClassName($token);
// 	createClassFile(
// 		$settings['directory'].DIRECTORY_SEPARATOR.$tokenClassName.'.java',
// 		sprintf($classTokenPattern, $settings['package'], $tokenClassName)
// 	);
// }

// Generate nonterminal files

$nonterminals = array_keys($grammar['nonterminals']);

foreach ($grammar['nonterminals'] as $nonterminal => $statements) {

	// For each nonterminal create class

	$className = labelToClassName($nonterminal);

	$constructors = array();
	foreach ($statements as $statement) {

		// For each statement create constructor

		$args = array();

		if (!empty($statement)) {
			$ss = preg_split("/\s+/", $statement);
			foreach ($ss as $s) {
				if (in_array($s, $nonterminals)) {
					$argClass = labelToClassName($s);
					$argVar = lcfirst($argClass);
					array_push($args, $argClass.' '.$argVar);
				} elseif (in_array($s, $settings['tokens'])) {
					$argVar = lcfirst($settings['parent_class_name']);
					array_push($args, $settings['parent'].' '.$argVar);
				}
			}
		}

		// checking for equal arguments
		foreach (array_count_values($args) as $value => $count) {
			if ($count > 1) {
				$j = 1;
				for ($i = 0; $i < count($args); $i++) {
					if ($args[$i] === $value) {
						$args[$i] .= $j;
						$j++;
					}
				}
			}
		}

		array_push($constructors, sprintf($constructorPattern, $className, join(', ', $args)));
	}

	$constructors = array_unique($constructors);

	createClassFile(
		$settings['directory'].DIRECTORY_SEPARATOR.$className.'.java',
		sprintf($classNonterminalPattern, $settings['package'], $className, join('', $constructors))
	);
}


function labelToClassName($label) {
	return preg_replace_callback('/(?:^|_)([a-z0-9])/', function ($matches) {
		return strtoupper($matches[1]);
	}, trim(strtolower($label)));
}

function getClassName($packageAndClass) {
	return end(explode('.', $packageAndClass));
}

function createClassFile($fileName, $src) {
	if (is_file($fileName)) {
		fprintf(STDERR, "file %s already exists\n", $fileName);
		exit(1);
	}
	if (file_put_contents($fileName, $src) === FALSE) {
		fprintf(STDERR, "cannot write file %s\n", $fileName);
		exit(1);
	}
	echo $fileName."\n";
}
