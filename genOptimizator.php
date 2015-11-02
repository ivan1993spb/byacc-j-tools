<?php

require_once dirname(__FILE__).'/parseArgs.php';

$settings = parseArgs(array(
	'package'    => '',
), $argc, $argv);

if (empty($settings['package'])) {
	fwrite(STDERR, "package cannot be empty\n");
	fwrite(STDERR, "use --package=org.some.pack.age\n");
	exit(1);
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

echo "new PTCallback() {\n";
echo "    @Override\n";
echo "    public void processElement(PTElement e, int level) {\n";
echo "        if (!(e instanceof PTNode)) {\n";
echo "            return;\n";
echo "        }\n";
echo "        PTNode ptnode = (PTNode)e;\n";
echo "        switch (ptnode.getNonterminal()) {\n";

foreach ($grammar['nonterminals'] as $nonterminal => $statements) {

	$iterativeStatements = array();
	
	foreach ($statements as $statement) {
		$ss = preg_split("/\s+/", $statement);
		
		if (sizeof($ss) == 2 && in_array($nonterminal, $ss)) {
			// This node may be binary tree
			array_push($iterativeStatements, $ss);
		}
	}

	if (!empty($iterativeStatements)) {
		echo "        case ".strtoupper($nonterminal).":\n";

		foreach ($iterativeStatements as $statement) {
			echo "            // childs = ".json_encode($statement)."\n";
			echo "            if (ptnode.getElements().size() == ".sizeof($statement).") {\n";

			foreach ($statement as $i => $s) {
				echo "                PTElement element$i = ptnode.getElements().get($i);\n";
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
				echo "                if (".join(" && ", $conditions).") {\n";
				echo "                    // Совпало! Переподвешиваем дочерние узлы вместо родительского узла\n";
				echo "                    break;\n";
				echo "                }\n";
			}

			echo "            }\n";
		}

		echo "            break;\n";
	}



}

echo "        }\n";
echo "    }\n";
echo "}\n";
