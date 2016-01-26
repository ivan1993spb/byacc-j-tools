byacc-j-tools
=============

generation slr table from yacc
------------------------------

```bash
$ ./yaccToSlr.php grammar.y
```

generation java classes from yacc
---------------------------------

```bash
$ ./genJavaClasses.php --package=com.test.pack.age --directory=test --parent=edu.eltech.moevm.ParserVar --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL grammar.y
```

generation java code for yacc 
-----------------------------

```bash
$ ./genYaccJava.php grammar.y --save_token_value=IDENTIFIER,STRING_LITERAL,CONSTANT > /tmp/grammar.y && mv /tmp/grammar.y ./
```
