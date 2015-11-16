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
		if ($this->name == INPUT_START || $this->name == INPUT_END) {
			return '{'.$this->name.'}';
		}
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

function getFollow($elementName, $grammar) {
	$tokens       = [];
	$nonterminals = [];
	$todo         = [$elementName];

	while (sizeof($todo) > 0) {
		$elementName = array_shift($todo);
		// issetFlag will be TRUE if rules which are contained current element exists 
		$issetFlag = FALSE;

		foreach ($grammar['rules'] as $rule) {
			$index = array_search($elementName, $rule[PARSE_GRAMMAR_STATEMENT]);

			if ($index !== FALSE) {
				if (!$issetFlag) {
					$issetFlag = TRUE;
				}
				
				if ($index+1 === sizeof($rule[PARSE_GRAMMAR_STATEMENT])) {
					// if is last element
					if (!in_array($rule[PARSE_GRAMMAR_NONTERMINAL], $todo)) {
						array_push($todo, $rule[PARSE_GRAMMAR_NONTERMINAL]);
					}
				} else {
					// else get next element
					$nextElementName = $rule[PARSE_GRAMMAR_STATEMENT][$index+1];

					if (in_array($nextElementName, $grammar['tokens'])) {
						if (!in_array($nextElementName, $tokens)) {
							array_push($tokens, $nextElementName);
						}
					} elseif (!in_array($nextElementName, $nonterminals)) {
						array_push($nonterminals, $nextElementName);
					}
				}
			}
		}

		if (!$issetFlag) {
			// curr elem is root if rules which are contained current element were not found
			array_push($tokens, INPUT_END);
		}
	}

	if (sizeof($nonterminals) > 0) {
		$parsedNonterminals = [];

		while (sizeof($nonterminals) > 0) {
			$nonterminalName = array_shift($nonterminals);
			array_push($parsedNonterminals, $nonterminalName);

			foreach ($grammar['rules'] as $rule) {
				if ($nonterminalName == $rule[PARSE_GRAMMAR_NONTERMINAL] &&
					sizeof($rule[PARSE_GRAMMAR_NONTERMINAL]) > 0) {

					$nextElementName = $rule[PARSE_GRAMMAR_STATEMENT][0];

					if (in_array($nextElementName, $grammar['tokens'])) {
						if (!in_array($nextElementName, $tokens)) {
							array_push($tokens, $nextElementName);
						}
					} elseif (!in_array($nextElementName, $nonterminals) &&
						!in_array($nextElementName, $parsedNonterminals)) {
						array_push($nonterminals, $nextElementName);
					}
				}
			}
		}
	}

	return $tokens;
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

fwrite(STDERR, "FOLLOW(x) table:\n");
foreach ($grammar['nonterminals'] as $element) {
	fprintf(STDERR, "FOLLOW(%s) = %s\n", $element, json_encode(getFollow($element, $grammar)));
}

?><!DOCTYPE html>
<html>
	<head>
		<title>SLR(1) Parser</title>
		<style type="text/css">
			table { display: inline-table; margin: 10px; }
		</style>
	</head>
	<body>
		<table border="1">
			<tbody>
				<? foreach ($grammar['rules'] as $ruleNumber => $rule): ?>
					<tr><td><?=$ruleNumber?></td><td><?=$rule[PARSE_GRAMMAR_RULE]?></td></tr>
				<? endforeach; ?>
			</tbody>
		</table>
		<table border="1">
			<thead>
				<tr><th>V<sub>p</sub></th><th></th></tr>
			</thead>
			<tbody>
				<? foreach ($tableStackSymbols as $marker => $elemSet): ?>
					<tr><td><?=$marker?></td><td><?=join(', ', $elemSet)?></td></tr>
				<? endforeach; ?>
			</tbody>
		</table>
		<table border="1">
			<thead>
				<tr>
					<th>OBLOW</th>
					<? foreach ($allElements as $element): ?>
						<th><?=$element?></th>
					<? endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<? foreach ($allElements as $element): ?>
					<tr>
						<td><?=$element?></td>
						<? foreach ($allElements as $_element): ?>
							<td><?=(getOblow($element, $_element, $grammar) ? "o" : "")?></td>
						<? endforeach; ?>
					</tr>
				<? endforeach; ?>
			</tbody>
		</table>
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

							foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $elementName) {

								echo '<td>';
								$oblowElems = array();
								foreach ($elements as $element) {
									foreach ($allElements as $_element) {
										if ($element->name == $elements[0]->name &&
											$_element->name == $elementName &&
											getOblow($element, $_element, $grammar)) {

											array_push($oblowElems, $_element);
										}
									}
								}

								$index = array_search($oblowElems, $tableStackSymbols);
								if ($index !== FALSE) {
									echo $index;
								}
								echo '</td>';
							}


						?>
					</tr>
				<? endforeach; ?>
			</tbody>
		</table>
		<table border="1">
			<thead>
				<tr>
					<th>f(a)</th>
					<? foreach ($grammar['tokens'] as $token):?>
						<th><?=$token?></th>
					<? endforeach; ?>
					<th><?=INPUT_END?></th>
				</tr>
			</thead>
			<tbody>
				<? foreach ($tableStackSymbols as $marker => $elements):?>
					<tr>
						<td><?=$marker?></td>
						<?


							foreach (array_merge($grammar['tokens'], [INPUT_END]) as $token) {
								echo '<td>';
								
								if ($token === INPUT_END) {
									$isset_0 = FALSE;
									foreach ($elements as $element) {
										if ($element->ruleNumber === 0 && $element->name != INPUT_START) {
											$isset_0 = TRUE;
											break;
										}
									}
									if ($isset_0) {
										echo 'Д';
										continue;
									}
								} else {
									foreach ($elements as $element) {
										foreach ($allElements as $_element) {
											if ($element->name == $elements[0]->name &&
												$_element->name == $token &&
												getOblow($element, $_element, $grammar)) {
												echo 'П';
												continue 3;
											}
										}
									}
								}

								foreach ($elements as $element) {
									if ($element->name == end($grammar['rules'][$element->ruleNumber][PARSE_GRAMMAR_STATEMENT])) {
										if (in_array($token, getFollow($grammar['rules'][$element->ruleNumber][PARSE_GRAMMAR_NONTERMINAL], $grammar))) {
											printf("С, %d", $element->ruleNumber);
											break;
										}
									}
								}

								echo '</td>';
							}

						?>
					</tr>
				<? endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
