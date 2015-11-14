<?php

require_once dirname(__FILE__).'/parseGrammar.php';

define("INPUT_START", "__start__");
define("INPUT_END", "__end__");

define("START_TIME", microtime(true));

class Element {
	public $name, $ruleNumber;
	
	function __construct($name, $ruleNumber) {
		$this->name = $name;
		$this->ruleNumber = $ruleNumber;
	}

	function __toString() {
		return sprintf("{%s|%d}", $this->name, $this->ruleNumber);
	}
}
function getOfirst(Element $element, $grammar) {
	if (in_array($element->name, $grammar['tokens'])) {
		return [$element];
	}

	$todo = [$element];
	$ofirst = [];

	while (sizeof($todo) > 0) {
		$elementCurrent = array_shift($todo);
		array_push($ofirst, $elementCurrent);

		if (in_array($elementCurrent->name, $grammar['nonterminals'])) {
			foreach ($grammar['rules'] as $ruleNumber => $rule) {
				if ($rule[PARSE_GRAMMAR_NONTERMINAL] == $elementCurrent->name) {
					$elementTodo = new Element($rule[PARSE_GRAMMAR_STATEMENT][0], $ruleNumber);
					if (!in_array($elementTodo, $ofirst) && !in_array($elementTodo, $todo)) {
						array_push($todo, $elementTodo);
					}
				}
			}
		}
	}

	return $ofirst;
}

function getOblow(Element $element1, Element $element2, $grammar) {
	$nextElement = null;

	if ($element1->name == INPUT_START) {
		if (empty($grammar['rules'])) {
			return FALSE;
		}
		// First element of first grammar rule
		$nextElement = new Element($grammar['rules'][0][PARSE_GRAMMAR_STATEMENT][0], 0);
	} else {
		$rule = $grammar['rules'][$element1->ruleNumber];
		$i = array_search($element1->name, $rule[PARSE_GRAMMAR_STATEMENT]);
		if ($i === FALSE || $i >= count($rule[PARSE_GRAMMAR_STATEMENT]) - 1) {
			return FALSE;
		}
		// First element after $element1
		$nextElement = new Element($rule[PARSE_GRAMMAR_STATEMENT][$i+1], $element1->ruleNumber);
	}

	$ofirstSet = getOfirst($nextElement, $grammar);

	if (empty($ofirstSet)) {
		return FALSE;
	}

	return in_array($element2, $ofirstSet);
}

$input = $argc > 1 ? $argv[1] : 'php://stdin';

$data = file_get_contents($input);
if ($data === FALSE) {
	fwrite(STDERR, "cannot read input\n");
	exit(1);
}

$grammar = parseGrammar($data);
if ($grammar === FALSE) {
	fwrite(STDERR, "invalid input\n");
	exit(1);
}

// Print rules
fwrite(STDERR, "Grammar rules:\n");
$indent = strlen(strval(count($grammar['rules']))) + 4;
foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	fprintf(STDERR, "%-".$indent."d%s\n", $ruleNumber, $ruleArr[PARSE_GRAMMAR_RULE]);
}

// Getting elements
$allElements = [new Element(INPUT_START, 0)];
foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	foreach ($ruleArr[PARSE_GRAMMAR_STATEMENT] as $element) {
		array_push($allElements, new Element($element, $ruleNumber));
	}
}

// Getting table stack symbols
$tableStackSymbols = array();
$markers = array('x', 'y', 'z', 'a', 'b', 'c');
foreach ($allElements as $element) {
	foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $elemName) {
		$elemSet = array();
		
		foreach ($allElements as $_element) {
			if ($_element->name == $elemName && getOblow($element, $_element, $grammar)) {
				array_push($elemSet, new Element($_element->name, $_element->ruleNumber));
			}
		}

		if (count($elemSet) > 1 && !in_array($elemSet, $tableStackSymbols)) {
			$i = 0;
			$j = 1;
			while (TRUE) {
				if ($i < count($markers)) {
					$marker = sprintf("{%s|%s}", $elemName, str_repeat($markers[$i], $j));
					if (!array_key_exists($marker, $tableStackSymbols)) {
						$tableStackSymbols[$marker] = $elemSet;
						break;
					}
					$i++;
				} else {
					$i = 0;
					$j++;
				}
			}
		}
	}
}

foreach ($allElements as $element) {
	foreach ($tableStackSymbols as $elemSet) {
		if (in_array($element, $elemSet)) {
			continue 2;
		}
	}
	$tableStackSymbols["$element"] = [$element];
}

fwrite(STDERR, "Table stack symbols:\n");
foreach ($tableStackSymbols as $marker => $elemSet) {
	fprintf(STDERR, "%s c (%s)\n", $marker, join(', ', $elemSet));
}

fprintf(STDERR, "exec time = %f\n", microtime(true)-START_TIME);
die();

?><!DOCTYPE html>
<html>
	<head>
		<title></title>
	</head>
	<body>
		<table border="1">
			<thead>
				<tr>
					<th>g(X)</th>
					<? foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $field): ?>
						<th><?=$field?></th>
					<? endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<? foreach ($tableStackSymbols as $marker => $elements):?>
					<tr>
						<td><?=$marker?></td>
						<?
							// foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $elementName) {
							// 	foreach ($elements as $element) {
							// 		if ($element->name == $elementName) {
							// 			echo '<td>'.$elementName.'</td>';
							// 			break;
							// 		}
							// 	}
							// }

						?>
					</tr>
				<? endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
