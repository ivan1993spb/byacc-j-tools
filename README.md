grammar-regexp-tool
===================

generation slr table from yacc
------------------------------

```bash
$ php yacctoslr.php < grammar.y
```

generation java classes from yacc
---------------------------------

```bash
$ php genJavaClasses.php --package=com.test.pack.age --directory=test --parent=edu.eltech.moevm.ParserVar --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL < grammar.y
```

generation java code for yacc 
-----------------------------

```bash
php genYJava.php --package="any.package" --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL,TYPE_NAME < grammar.y 
```
