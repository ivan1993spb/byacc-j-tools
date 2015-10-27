grammar-regexp-tool
===================

generation slr table from yacc
------------------------------

```bash
$ php yacctoslr.php < grammar.y
```

generation java code from yacc
------------------------------

```bash
$ php genJava.php --package=com.test.pack.age --directory=test --parent=edu.eltech.moevm.ParserVar --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL < grammar.y
```
