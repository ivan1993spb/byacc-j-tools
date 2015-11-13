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

$indent = strlen(strval(count($grammar))) + 4;

foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	fprintf(STDERR, "%-".$indent."d%s\n", $ruleNumber, $ruleArr[PARSE_GRAMMAR_RULE]);
}


$elements = array(array('_|_', 0));
define("ELEMENT_NAME", 0);
define("ELEMENT_RULE_NUMBER", 1);

foreach ($grammar['rules'] as $ruleNumber => $ruleArr) {
	foreach ($ruleArr[PARSE_GRAMMAR_STATEMENT] as $element) {
		array_push($elements, array($element, $ruleNumber));
	}
}

// print_r($elements);
// print_r(ofirst($elements[5], $grammar));

foreach ($elements as $element) {
	echo json_encode($element);
	echo ": ";
	echo json_encode(ofirst($element, $grammar));
	echo "\n";	 
}


///////////////////// GOOD TEST ///////////////////////
// foreach ($elements as $element1) {
// 	foreach ($elements as $element2) {
// 		if (oblow($element1, $element2, $grammar))
// 		printf("%s%d OBLOW %s%d == %d\n", $element1[ELEMENT_NAME], $element1[ELEMENT_RULE_NUMBER],
// 			$element2[ELEMENT_NAME], $element2[ELEMENT_RULE_NUMBER], oblow($element1, $element2, $grammar));
		
// 		// echo " == ". (oblow($element1, $element2, $grammar) ? "1" : "0");

// 		// echo "\n";
// 	}
// }
///////////////////////////////////////////////////////////////////////////////////
// print_r($elements);


/*

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
			<?php foreach ($elements as $element):?>
				<tr>
					<td><?=$element[ELEMENT_NAME]?><sub><?=$element[ELEMENT_RULE_NUMBER]?></sub></td>
					<?php


						foreach (array_merge($grammar['tokens'], $grammar['nonterminals']) as $value){
							echo '<td>';
						 	foreach ($elements as $_element) {
						 		if ($_element[ELEMENT_NAME] == $value && oblow($element, $_element, $grammar)) {
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
//*/
// print_r($elements);

// define("RULE_NUMBER", 1);

// foreach ($grammar as $ruleNumber => $ruleArr) {
// 	foreach ($ruleArr[PARSE_GRAMMAR_STATEMENT] as $element) {
// 		array_push($elements, array($element, $ruleNumber));
// 	}
// }

echo "\n";

function ofirst($element, $grammar) {
	if (in_array($element[ELEMENT_NAME], $grammar['tokens'])) {
		return array($element);
	}
	unset($grammar['rules'][$element[ELEMENT_RULE_NUMBER]]);
	$ofirst = array($element);
	
	foreach ($grammar['rules'] as $ruleNumber => $rule) {
		// if ($ruleNumber == $element[ELEMENT_RULE_NUMBER]) {
			// continue;
		// }
		if ($rule[PARSE_GRAMMAR_NONTERMINAL] == $element[ELEMENT_NAME]) {

			$_ofirst = ofirst(
				//      0 1 2
				// E -> B + C
				array($rule[PARSE_GRAMMAR_STATEMENT][0], $ruleNumber),
				$grammar
			);

			foreach ($_ofirst as $_element) {
				foreach ($ofirst as $__element) {
					if ($_element[ELEMENT_NAME] == $__element[ELEMENT_NAME]
						&& $_element[ELEMENT_RULE_NUMBER] ==
						$__element[ELEMENT_RULE_NUMBER]) {
						continue 2;
					}
				}
				array_push($ofirst, $_element);
			}
		}
	}

	return $ofirst;
}


echo "--------------------\n";
foreach ($elements as $element) {
	echo json_encode($element);
	echo ": ";
	$_ofirst = array();
	ofirst1($element, $grammar, $_ofirst);
	echo json_encode($_ofirst);
	echo "\n";	 
}

function ofirst1($element, $grammar, &$_ofirst=array()) {
	if (!is_array($_ofirst)) {
		$_ofirst = array();
	}

	if (in_array($element, $_ofirst)) {
		return;
	}
	
	array_push($_ofirst, $element);

	if (in_array($element[ELEMENT_NAME], $grammar['tokens'])) {
		return;
	}

	foreach ($grammar['rules'] as $ruleNumber => $rule) {
		if ($rule[PARSE_GRAMMAR_NONTERMINAL] == $element[ELEMENT_NAME]) {
			$current = array($rule[PARSE_GRAMMAR_STATEMENT][0], $ruleNumber);
			ofirst1($current, $grammar, $_ofirst);
		}
	}
}

function oblow($element1, $element2, $grammar) {
	if ($element1[ELEMENT_NAME] == "_|_") {
		$ofirst_element1 = ofirst(array($grammar['rules'][0][PARSE_GRAMMAR_STATEMENT][0], 0), $grammar);

		foreach ($ofirst_element1 as $_element) {
			if ($_element[ELEMENT_NAME] == $element2[ELEMENT_NAME] && $_element[ELEMENT_RULE_NUMBER] == $element2[ELEMENT_RULE_NUMBER]) {
				// echo 'ofirst('.json_encode(array($rule[PARSE_GRAMMAR_STATEMENT][$i+1], $ruleNumber)).') == ';
				// echo json_encode($ofirst_element1). " // ";
				// echo $rule[PARSE_GRAMMAR_RULE]."\n";
				return TRUE;
			}
		}

		return FALSE;
	}

	$rule = $grammar['rules'][$element1[ELEMENT_RULE_NUMBER]];
	$ruleNumber = $element1[ELEMENT_RULE_NUMBER];

	$i = array_search($element1[ELEMENT_NAME], $rule[PARSE_GRAMMAR_STATEMENT]);

	if ($i !== FALSE && $i < count($rule[PARSE_GRAMMAR_STATEMENT]) - 1) {
		$ofirst_element1 = ofirst(array($rule[PARSE_GRAMMAR_STATEMENT][$i+1], $ruleNumber), $grammar);


		foreach ($ofirst_element1 as $_element) {
			if ($_element[ELEMENT_NAME] == $element2[ELEMENT_NAME] && $_element[ELEMENT_RULE_NUMBER] == $element2[ELEMENT_RULE_NUMBER]) {
				// echo 'ofirst('.json_encode(array($rule[PARSE_GRAMMAR_STATEMENT][$i+1], $ruleNumber)).') == ';
				// echo json_encode($ofirst_element1). " // ";
				// echo $rule[PARSE_GRAMMAR_RULE]."\n";
				return TRUE;
			}
		}
	}

	return FALSE;
}
/*

епсилон E = EOF
Допуск достигли корня

3 E -> a b c E d

ofirst(E3) == E3, a

*/
