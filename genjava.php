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

$packpath = str_replace('.', DIRECTORY_SEPARATOR, $settings['package']);
if (substr($settings['directory'], -strlen($packpath)) !== $packpath) {
	fwrite(STDERR, "invalid package or directory:\n");
	fprintf(STDERR, "target directory %s must ends with %s\n", $settings['directory'], $packpath);
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

$parentClassName = getClassName($settings['parent']);
$tokensToGenerate = explode(',', strtolower($settings['tokens']));

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

public class Token extends %s {
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
	sprintf($nonterminalFilePattern, $settings['package'], $settings['parent'], $parentClassName)
);

if (!empty($settings['tokens'])) {
	// Create parent class for token classes
	createClassFile(
		$settings['directory'].DIRECTORY_SEPARATOR.'Token.java',
		sprintf($tokenFilePattern, $settings['package'], $settings['parent'], $parentClassName,
			$parentClassName, lcfirst($parentClassName))
	);
}

// Start parsing

require_once dirname(__FILE__).'/parseYacc.php';

$input = null;
if ($argc > 1) {
	$input = array_slice($argv, 1);
} else {
	$input = array('php://stdin');
}

foreach ($input as $filein) {
	$data = file_get_contents($filein);

	$grammar = parseYacc($data);
	if ($grammar === FALSE) {
		fwrite(STDERR, "invalid input");
		exit(1);
	}

	$grammar['tokens'] = array_map('strtolower', $grammar['tokens']);

	// Generate token files
	foreach (array_intersect($grammar['tokens'], $tokensToGenerate) => $token) {
		$tokenClassName = nonterminalToClassName($token);
		createClassFile(
			$settings['directory'].DIRECTORY_SEPARATOR.$tokenClassName.'.java',
			sprintf($classTokenPattern, $settings['package'], $tokenClassName)
		);
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

		$fileName = $directory.DIRECTORY_SEPARATOR.$className.'.java';
		if (file_put_contents($fileName, $src) === FALSE) {
			echo "cannot write class $className\n";
			exit(1);
		} else {
			echo $fileName."\n";
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
