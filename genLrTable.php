<?php

require_once dirname(__FILE__).'/parseGrammar.php';

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
$indent = strlen(strval(count($grammar))) + 4;
foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	fprintf(STDERR, "%-".$indent."d%s\n", $ruleNumber, $ruleArr[PARSE_GRAMMAR_RULE]);
}

define("INPUT_START", "__start__");
define("INPUT_END", "__end__");

define("ELEMENT_NAME", 0);
define("ELEMENT_RULE_NUMBER", 1);

/*
 * $allElements = [
 *   [
 *     ELEMENT_NAME        => ... ,
 *     ELEMENT_RULE_NUMBER => ...
 *   ],
 *   ...
 * ]
 */

$allElements = [[INPUT_START, 0]];
foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	foreach ($ruleArr[PARSE_GRAMMAR_STATEMENT] as $element) {
		array_push($allElements, array($element, $ruleNumber));
	}
}

class Table implements Iterator {
	private $cols     = [];
	private $rows     = [];
	private $values   = [[]];
	private $position = 0;

	function setValue($col, $row, $value) {
		if ($this->position !== 0) {
			return;
		}

		if (!in_array($col, $this->cols)) {
			array_push($this->cols, $col);
		}
		if (!in_array($row, $this->rows)) {
			array_push($this->rows, $row);
		}

		$i = array_search($col, $this->cols);
		$j = array_search($row, $this->rows);

		$this->values[$i][$j] = $value;
		print_r($this->values);
	}

	function getValue($col, $row) {
		$i = array_search($col, $this->cols);
		if ($i === FALSE) {
			return null;
		}

		$j = array_search($row, $this->rows);
		if ($j === FALSE) {
			return null;
		}

		return $this->values[$i][$j];
	}

	function getWidth() {
		return count($this->cols);
	}

	function getHeight() {
		return count($this->rows);
	}

	function exists($col, $row) {
		$i = array_search($col, $this->cols);
		if ($i === FALSE) {
			return FALSE;
		}

		$j = array_search($row, $this->rows);
		if ($j === FALSE) {
			return FALSE;
		}

		return TRUE;
	}

	function rewind() {
		$this->position = 0;
	}

	function current() {
		list($i, $j) = $this->_getIJ();
		return $this->values[$i][$j];
	}

	private function _getIJ() {
		$i = $this->position % count($this->cols);
		$j = ($this->position - $this->position%count($this->cols)) / count($this->cols);
		return [$i, $j]
	}

	function key() {
		return $this->position;
	}

	function next() {
		++$this->position;
	}

	function valid() {
		return (count($this->cols) * count($this->rows)) > $this->position;
	}
}

$t = new Table();
$t->setValue(1, 2, 33);
$t->setValue(2, 1, 44);
$t->setValue(30, 1, 66);

echo ':::';
foreach ($t as $key => $value) {
	echo "\n\n";
}
var_dump($t->getValue(1, 2));
var_dump($t->getValue(2, 1));
var_dump($t->getValue(30, 1));
var_dump($t->getValue(1, 2));


$tableStackSymbols = array();
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
				<th></th>
				<?php foreach ($grammar['tokens'] as $token) : ?>
					<th><?=$token?></th>
				<?php endforeach; ?>
				<?php foreach ($grammar['nonterminals'] as $nonterminal) : ?>
					<th><?=$nonterminal?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($allElements as $element):?>
				<tr>
					<td><?=$element[ELEMENT_NAME]?><sub><?=$element[ELEMENT_RULE_NUMBER]?></sub></td>
					<?php


						foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $value){
							echo '<td>';
						 	foreach ($allElements as $_element) {
						 		if ($_element[ELEMENT_NAME] == $value && getOblow($element, $_element, $grammar)) {
						 			echo $_element[ELEMENT_NAME].'<sub>'.$_element[ELEMENT_RULE_NUMBER]."</sub>\n";
						 		}
						 	}
							echo '</td>';
						}


					?>
				</tr>

			<?php endforeach; ?>
		</tbody>
	</table>
	</body>
</html>


<?php

function getOfirst($element, $grammar, &$_ofirst=array()) {
	if (!is_array($_ofirst)) {
		$_ofirst = array();
	}

	if (in_array($element, $_ofirst)) {
		return $_ofirst;
	}
	
	array_push($_ofirst, $element);

	if (in_array($element[ELEMENT_NAME], $grammar['tokens'])) {
		return $_ofirst;
	}

	foreach ($grammar['rules'] as $ruleNumber => $rule) {
		if ($rule[PARSE_GRAMMAR_NONTERMINAL] == $element[ELEMENT_NAME]) {
			$current = array($rule[PARSE_GRAMMAR_STATEMENT][0], $ruleNumber);
			getOfirst($current, $grammar, $_ofirst);
		}
	}

	return $_ofirst;
}

function getOblow($element1, $element2, $grammar) {
	$nextElement = null;

	if ($element1[ELEMENT_NAME] == INPUT_START) {
		$nextElement = array($grammar['rules'][0][PARSE_GRAMMAR_STATEMENT][0], 0);
	} else {
		$rule = $grammar['rules'][$element1[ELEMENT_RULE_NUMBER]];
		$i = array_search($element1[ELEMENT_NAME], $rule[PARSE_GRAMMAR_STATEMENT]);
		if ($i === FALSE || $i >= count($rule[PARSE_GRAMMAR_STATEMENT]) - 1) {
			return FALSE;
		}
		$nextElement = array($rule[PARSE_GRAMMAR_STATEMENT][$i+1], $element1[ELEMENT_RULE_NUMBER]);
	}

	$ofirstSet = getOfirst($nextElement, $grammar);
	if (empty($ofirstSet)) {
		return FALSE;
	}

	return in_array($element2, $ofirstSet);
}
