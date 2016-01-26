#!/usr/bin/env php
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

class CachedOfirst {
	const ELEM   = 0;
	const OFIRST = 1;

	private $cache = [];
	private $grammar;

	function __construct($grammar) {
		$this->grammar = $grammar;
	}

	function getOfirst(Element $element) {
		if (in_array($element->name, $this->grammar['tokens'])) {
			return [$element];
		}
		
		foreach ($this->cache as $row) {
			if ($row[self::ELEM] == $element) {
				return $row[self::OFIRST];
			}
		}

		$ofirst = getOfirst($element, $this->grammar);

		array_push($this->cache, array(
			self::ELEM   => $element,
			self::OFIRST => $ofirst
		));

		return $ofirst;
	}
}

function getFollow($elementName, $grammar) {
	$tokens       = [];
	$nonterminals = [];

	$todo = [$elementName];
	$done = [];
	
	// Find all next elements
	while (sizeof($todo) > 0) {
		$elementName = array_shift($todo);
		array_push($done, $elementName);
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
					if (!in_array($rule[PARSE_GRAMMAR_NONTERMINAL], $todo) &&
						!in_array($rule[PARSE_GRAMMAR_NONTERMINAL], $done)) {
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

		// Find all first tokens
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

class OblowGrammar {
	private $grammar;
	private $ofirstCache;

	function __construct($grammar) {
		$this->grammar = $grammar;
		$this->ofirstCache = new CachedOfirst($grammar);
	}

	function getOblow(Element $element1, Element $element2) {
		$nextElement = null;

		if ($element1->name == INPUT_START) {
			if (empty($this->grammar['rules'])) {
				return FALSE;
			}
			// First element of first grammar rule
			$nextElement = new Element($this->grammar['rules'][0][PARSE_GRAMMAR_STATEMENT][0], 0);
		} else {
			$rule = $this->grammar['rules'][$element1->ruleNumber];
			$i = array_search($element1->name, $rule[PARSE_GRAMMAR_STATEMENT]);
			if ($i === FALSE || $i >= count($rule[PARSE_GRAMMAR_STATEMENT]) - 1) {
				return FALSE;
			}
			// First element after $element1
			$nextElement = new Element($rule[PARSE_GRAMMAR_STATEMENT][$i+1], $element1->ruleNumber);
		}

		$ofirstSet = $this->ofirstCache->getOfirst($nextElement);

		if (empty($ofirstSet)) {
			return FALSE;
		}

		return in_array($element2, $ofirstSet);
	}
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

fprintf(STDERR, "all elements: %d\n", count($allElements));
fwrite(STDERR, "caching...\r");

$oblowGrammar = new OblowGrammar($grammar);

function getMarker($i) {
	$symbols = ['x', 'y', 'z', 'a', 'b', 'c'];
	$conv = base_convert($i, 10, count($symbols));
	$marker = '';

	for ($j = 0; $j < strlen($conv); $j++) {
		$marker .= $symbols[(int)$conv[$j]];
	}

	return $marker;
}

// Getting table stack symbols
$tableStackSymbols = array();

foreach ($allElements as $element) {
	$oblowRow = [];
	$names    = [];
	
	foreach ($allElements as $_element) {
		if ($oblowGrammar->getOblow($element, $_element)) {
			array_push($oblowRow, $_element);

			if (!in_array($_element->name, $names)) {
				array_push($names, $_element->name);
			}
		}
	}

	foreach ($names as $elementName) {
		$elements = [];

		foreach ($oblowRow as $_element) {
			if ($_element->name == $elementName) {
				array_push($elements, $_element);
			}
		}

		if (in_array($elements, $tableStackSymbols)) {
			continue;
		}

		if (count($elements) > 1) {
			$i = 0;

			while (TRUE) {
				$marker = sprintf("{%s|%s}", $elementName, getMarker($i));

				if (!array_key_exists($marker, $tableStackSymbols)) {
					$tableStackSymbols[$marker] = $elements;
					break;
				}

				$i++;
			}
		} elseif (count($elements) == 1) {
			$marker = $elements[0]->__toString();

			if (!array_key_exists($marker, $tableStackSymbols)) {
				$tableStackSymbols[$marker] = $elements;
			}
		}
	}
}

$_tableStackSymbols = [];

foreach ($tableStackSymbols as $elements) {
	if (count($elements) > 1) {
		$oblowRow = [];
		$names    = [];

		foreach ($elements as $element) {
			foreach ($allElements as $_element) {
				if ($oblowGrammar->getOblow($element, $_element)) {
					array_push($oblowRow, $_element);

					if (!in_array($_element->name, $names)) {
						array_push($names, $_element->name);
					}
				}
			}
		}

		foreach ($names as $elementName) {
			$elements = [];

			foreach ($oblowRow as $_element) {
				if ($_element->name == $elementName) {
					array_push($elements, $_element);
				}
			}

			if (in_array($elements, $tableStackSymbols)) {
				continue;
			}

			if (count($elements) > 1) {
				$i = 0;

				while (TRUE) {
					$marker = sprintf("{%s|%s}", $elementName, getMarker($i));

					if (!array_key_exists($marker, $tableStackSymbols) &&
						!array_key_exists($marker, $_tableStackSymbols)) {
						$_tableStackSymbols[$marker] = $elements;
						break;
					}

					$i++;
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

$tableStackSymbols = array_merge($tableStackSymbols, $_tableStackSymbols);

fwrite(STDERR, "Table stack symbols:\n");
foreach ($tableStackSymbols as $marker => $elemSet) {
	fprintf(STDERR, "%s c (%s)\n", $marker, join(', ', $elemSet));
}

//die();

fwrite(STDERR, "FOLLOW(x) table:\n");
foreach ($grammar['nonterminals'] as $element) {
	fprintf(STDERR, "FOLLOW(%s) = %s\n", $element, json_encode(getFollow($element, $grammar)));
}

fwrite(STDERR, "writing HTML...\r");

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>SLR(1) Parser</title>
		<style type="text/css">
			table { display: inline-table; margin: 10px; }
			td, th { text-align: center; padding: 5px; }
			tbody tr td:first-child { font-weight: bold; }
			.sel { background: #ddd; }
		</style>

		<? if (is_file("jquery-2.1.4.min.js")): ?>
			<script type="text/javascript">
				<?=file_get_contents("jquery-2.1.4.min.js")?>
			</script>
		<? else: ?>
			<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
		<? endif; ?>
		
		<script type="text/javascript">
		$(function(){
			$('table tbody td, table thead th').click(function () {
				$(this).parent().parent().parent().find("th, td, tr").removeClass('sel');
				$(this).parent().addClass('sel');
				$(this).parent().parent().parent()
					.find('th:nth-child(' + ($(this).index() + 1) + '),'+
						' td:nth-child(' + ($(this).index() + 1) + ')').addClass('sel');
			});
		});
		</script>
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
							<td><?=($oblowGrammar->getOblow($element, $_element) ? "o" : "")?></td>
						<? endforeach; ?>
					</tr>
				<? endforeach; ?>
			</tbody>
		</table>

		<table border="1">
			<thead>
				<tr>
					<th>g(X)</th>
					<? foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $fieldName): ?>
						<th><?=$fieldName?></th>
					<? endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<? foreach ($tableStackSymbols as $marker => $elements): ?>
					<tr>
						<td><?=$marker?></td>
						<? foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $elementName): ?>
							<td><?php

								$oblowElems = array();

								foreach ($elements as $element) {
									foreach ($allElements as $_element) {
										if (
											$_element->name == $elementName &&
											$oblowGrammar->getOblow($element, $_element)) {
											if (!in_array($_element, $oblowElems)) {
												array_push($oblowElems, $_element);
											}
										}
									}
								}

								foreach ($tableStackSymbols as $index => $_elements) {
									if (count($_elements) != count($oblowElems)) {
										continue;
									}

									foreach ($oblowElems as $_element) {
										if (!in_array($_element, $_elements)) {
											continue 2;
										}
									}

									echo $index;
									break;
								}

								// $index = array_search($oblowElems, $tableStackSymbols);

								// if ($index !== FALSE) {
								// 	echo $index;
								// } else
								// if ($marker == '{DECLARATION_SPECIFIERS|x}') {

									// if (!empty($oblowElems)) {
										// echo json_encode($oblowElems);
										// echo "<br/>";
										// echo json_encode($tableStackSymbols);

									// }
								// }
							?></td>
						<? endforeach; ?>
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
						<? foreach (array_merge($grammar['tokens'], [INPUT_END]) as $token): ?>
							<td><?php
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
										goto closeField;
									}
								} else {
									foreach ($elements as $element) {
										foreach ($allElements as $_element) {
											if (
												$_element->name == $token &&
												$oblowGrammar->getOblow($element, $_element)) {
												echo 'П';
												goto closeField;
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
								closeField:
							?></td>
						<? endforeach; ?>
					</tr>
				<? endforeach; ?>
			</tbody>
		</table>
	</body>
</html>
<?php

fprintf(STDERR, "exec time: %f sec\n", microtime(true)-START_TIME);
