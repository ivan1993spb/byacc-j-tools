#!/usr/bin/env php
<?php

require_once dirname(__FILE__).'/parseArgs.php';
require_once dirname(__FILE__).'/parseYacc.php';

$settings = parseArgs(array(
	'package'    => '',
), $argc, $argv);

if (empty($settings['package'])) {
	fwrite(STDERR, "package cannot be empty\n");
	fwrite(STDERR, "use --package=org.some.pack.age\n");
	exit(1);
}

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

printf("package %s;\n", $settings['package']);
echo "\n";
echo "import edu.eltech.moevm.autogen.Parser;\n";
echo "\n";
echo "public class TreeRecursionOptimizer implements PTCallback {\n";
echo "    @Override\n";
echo "    public void processElement(PTElement e, int level) {\n";
echo "        if (!(e instanceof PTNode)) {\n";
echo "            return;\n";
echo "        }\n";
echo "        PTNode ptnode = (PTNode)e;\n";
echo "        for (int i = 0; i < ptnode.getElements().size(); i++) {\n";
echo "            PTElement child = ptnode.getElements().get(i);\n";
echo "            if (!(child instanceof PTNode)) {\n";
echo "                continue;\n";
echo "            }\n";
echo "            PTNode ptnodeChild = (PTNode)child;\n";
echo "            switch (ptnodeChild.getNonterminal()) {\n";

foreach ($grammar['nonterminals'] as $nonterminal => $statements) {

	$iterativeStatements = array();
	
	foreach ($statements as $statement) {
		$ss = preg_split("/\s+/", $statement);
		
		if (in_array($nonterminal, $ss) /*&& empty(array_intersect($ss, $grammar['tokens'])) && */ &&
			(sizeof($ss) == 2 || in_array($nonterminal, array("init_declarator_list")))) {
			array_push($iterativeStatements, $ss);
		}
	}

	if (!empty($iterativeStatements)) {
		echo "                case ".strtoupper($nonterminal).":\n";

		foreach ($iterativeStatements as $statement) {
			echo "                    // childs = ".json_encode($statement)."\n";
			echo "                    if (ptnodeChild.getElements().size() == ".sizeof($statement).") {\n";

			foreach ($statement as $i => $s) {
				echo "                        PTElement element$i = ptnodeChild.getElements().get($i);\n";
			}

			$conditions = array();
			foreach ($statement as $i => $s) {
				if (array_key_exists($s, $grammar['nonterminals'])) {
					array_push($conditions, "element$i instanceof PTNode");
					array_push($conditions, "((PTNode)element{$i}).getNonterminal() == Nonterminals.".strtoupper($s));
				} else {
					array_push($conditions, "element$i instanceof PTLeaf");
					array_push($conditions, "((PTLeaf)element{$i}).getToken() == Parser.".$s);
				}
			}

			if (!empty($conditions)) {
				echo "                        if (".join(" && ", $conditions).") {\n";

				for ($i = 0; $i < sizeof($statement); $i++) {
					echo "                            ptnode.insertElementBefore(child, element$i);\n";
				}
				echo "                            System.out.println(\"(recursive) removed \" + ptnodeChild.getNonterminal());\n";
				echo "                            ptnode.remove(child);\n";
				echo "                            i--;\n";
				echo "                            break;\n";
				echo "                        }\n";
			}

			echo "                    }\n";
		}

		echo "                    break;\n";
	}
}

echo "            }\n";
echo "        }\n";
echo "    }\n";
echo "}\n";
