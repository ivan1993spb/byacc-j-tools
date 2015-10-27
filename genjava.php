<?php

define("FLAG_PACKAGE", "--package=");

$package = "genjava";
if ($argc > 1) {
	foreach ($argv as $key => $value) {
		if (substr($value, 0,  strlen(FLAG_PACKAGE)) === FLAG_PACKAGE) {
			$value = str_replace(FLAG_PACKAGE, "", $value);
			$package = empty($value) ? $package : $value;
			$argc -= 1;
			unset($argv[$key]);
			break;
		}
	}
}

$directory = getcwd().DIRECTORY_SEPARATOR.join(DIRECTORY_SEPARATOR, explode('.', $package));
if (is_dir($directory)) {
	if (!($files = @scandir($directory)) || count($files) > 2) {
		echo "directory $directory is not empty\n";
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
