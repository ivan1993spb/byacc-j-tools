byacc-j-tools
=============

## generate yacc map

```bash
$ ./genHtml grammar.y > map.html
```

## generate java classes from yacc

```bash
$ ./genJavaClasses --package=org.some.pack.age --parent=org.some.pack.age.ParentClass --directory=test --tokens=IDENTIFIER,CONSTANT,STRING_LITERAL grammar.y
```

## generate grammar tables

```bash
$ ./genLrTable grammar.txt > tables.html
```

## generate nonterminal java enum

```bash
$ ./genNonterminalEnum --package=any.package grammar.y
```

## generate tree recursion optimizator

```bash
$ ./genOptimizator --package=any.package grammar.y
```

## generate java code for yacc file 

```bash
$ ./genYaccJava --save_token_value=IDENTIFIER,STRING_LITERAL,CONSTANT grammar.y > example.y
```

## convert yacc grammar into normal form

```bash
$ ./yaccToSlr grammar.y
```
