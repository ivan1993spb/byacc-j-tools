<?php

function parseArgs($settings, &$argc, &$argv) {
	foreach ($argv as $index => $argstr) {
		if (preg_match('/^--?([a-z_][a-z0-9_-]*)=(\S+)$/i', $argstr, $matches) === 1) {
			if (array_key_exists($matches[1], $settings)) {
				$settings[$matches[1]] = $matches[2];
				
				unset($argv[$index]);
				$argc--;
			}
		}
	}

	$argv = array_values($argv);

	return $settings;
}
