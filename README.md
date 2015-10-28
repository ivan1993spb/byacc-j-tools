byacc-j-tools
=============

generation slr table from yacc
------------------------------

```bash
$ php yaccToSlr.php grammar.y
```

generation java classes from yacc
---------------------------------

```bash
$ php genJavaClasses.php --package=com.test.pack.age --directory=test --parent=edu.eltech.moevm.ParserVar --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL grammar.y
```

generation java code for yacc 
-----------------------------

```bash
$ php genYaccJava.php --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL,TYPE_NAME grammar.y 
```
