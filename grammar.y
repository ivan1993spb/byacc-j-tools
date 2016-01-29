%{
  import java.io.*;
  import edu.eltech.moevm.*;
  import edu.eltech.moevm.common.*;
  import edu.eltech.moevm.parsing_tree.*;
%}
      
%token IDENTIFIER CONSTANT STRING_LITERAL SIZEOF
%token INC_OP DEC_OP LEFT_OP RIGHT_OP LE_OP GE_OP EQ_OP NE_OP
%token AND_OP OR_OP

%token STATIC
%token CHAR SHORT INT LONG FLOAT DOUBLE VOID COMPLEX BOOL

%token RE IM SRE SIM MOD PRINT NEW

%token IF ELSE WHILE DO FOR GOTO BREAK RETURN

%token SEMICOLON BRACELEFT BRACERIGHT COMMA COLON EQUAL RBLEFT RBRIGHT BRACKETLEFT
%token BRACKETRIGHT DOT AMP EXCL MINUS PLUS STAR DIVIDE PERCENT LESS GREATER
%token CARET BAR QUESTION NUMBER_SIGN


%start root
%%

primary_expression
	: IDENTIFIER
	| CONSTANT
	| STRING_LITERAL
	| RBLEFT expression RBRIGHT
	;

postfix_expression
	: primary_expression
	| postfix_expression BRACKETLEFT expression BRACKETRIGHT
	| postfix_expression RBLEFT RBRIGHT
	| postfix_expression RBLEFT argument_expression_list RBRIGHT
	| postfix_expression INC_OP
	| postfix_expression DEC_OP
	;

argument_expression_list
	: assignment_expression
	| argument_expression_list COMMA assignment_expression
	;

unary_expression
	: postfix_expression
	| INC_OP unary_expression
	| DEC_OP unary_expression
	| MINUS cast_expression
	| EXCL cast_expression
	| NEW type_specifier BRACKETLEFT expression BRACKETRIGHT
	| SIZEOF unary_expression
	| SIZEOF RBLEFT type_specifier RBRIGHT
	| RE RBLEFT CONSTANT RBRIGHT
	| RE RBLEFT IDENTIFIER RBRIGHT
	| IM RBLEFT CONSTANT RBRIGHT
	| IM RBLEFT IDENTIFIER RBRIGHT
	| SRE RBLEFT IDENTIFIER COMMA IDENTIFIER RBRIGHT
	| SIM RBLEFT IDENTIFIER COMMA IDENTIFIER RBRIGHT
	| MOD RBLEFT CONSTANT RBRIGHT
	| MOD RBLEFT IDENTIFIER RBRIGHT
	| PRINT RBLEFT STRING_LITERAL RBRIGHT
	| PRINT RBLEFT IDENTIFIER RBRIGHT
	;

cast_expression
	: unary_expression
	| RBLEFT type_specifier RBRIGHT cast_expression
	;

multiplicative_expression
	: cast_expression
	| multiplicative_expression STAR cast_expression
	| multiplicative_expression DIVIDE cast_expression
	| multiplicative_expression PERCENT cast_expression
	;

additive_expression
	: multiplicative_expression
	| additive_expression PLUS multiplicative_expression
	| additive_expression MINUS multiplicative_expression
	;

shift_expression
	: additive_expression
	| shift_expression LEFT_OP additive_expression
	| shift_expression RIGHT_OP additive_expression
	;

relational_expression
	: shift_expression
	| relational_expression LESS shift_expression
	| relational_expression GREATER shift_expression
	| relational_expression LE_OP shift_expression
	| relational_expression GE_OP shift_expression
	;

equality_expression
	: relational_expression
	| equality_expression EQ_OP relational_expression
	| equality_expression NE_OP relational_expression
	;

and_expression
	: equality_expression
	| and_expression AMP equality_expression
	;

exclusive_or_expression
	: and_expression
	| exclusive_or_expression CARET and_expression
	;

inclusive_or_expression
	: exclusive_or_expression
	| inclusive_or_expression BAR exclusive_or_expression
	;

logical_and_expression
	: inclusive_or_expression
	| logical_and_expression AND_OP inclusive_or_expression
	;

logical_or_expression
	: logical_and_expression
	| logical_or_expression OR_OP logical_and_expression
	;

conditional_expression
	: logical_or_expression
	| logical_or_expression QUESTION expression COLON conditional_expression
	;

assignment_expression
	: conditional_expression
	| postfix_expression EQUAL assignment_expression
	;

expression
	: assignment_expression
	| expression COMMA assignment_expression
	;

constant_expression
	: conditional_expression
	;

declaration
	: declaration_specifiers SEMICOLON
	| declaration_specifiers init_declarator_list SEMICOLON
	;

declaration_specifiers
	: type_specifier
	| type_specifier declaration_specifiers
	;

init_declarator_list
	: init_declarator
	| init_declarator_list COMMA init_declarator
	;

init_declarator
	: direct_declarator
	| direct_declarator EQUAL initializer
	;

type_specifier
	: VOID
	| COMPLEX
	| CHAR
	| SHORT
	| INT
	| LONG
	| FLOAT
	| DOUBLE
	| BOOL
	;

direct_declarator
	: IDENTIFIER
	| RBLEFT direct_declarator RBRIGHT
	| direct_declarator BRACKETLEFT constant_expression BRACKETRIGHT
	| direct_declarator BRACKETLEFT BRACKETRIGHT
	| direct_declarator RBLEFT parameter_list RBRIGHT
	| direct_declarator RBLEFT identifier_list RBRIGHT
	| direct_declarator RBLEFT RBRIGHT
	;

parameter_list
	: parameter_declaration
	| parameter_list COMMA parameter_declaration
	;

parameter_declaration
	: declaration_specifiers direct_declarator
	| declaration_specifiers abstract_declarator
	| declaration_specifiers
	;

identifier_list
	: IDENTIFIER
	| identifier_list COMMA IDENTIFIER
	;

abstract_declarator
	: direct_abstract_declarator
	;

direct_abstract_declarator
	: RBLEFT abstract_declarator RBRIGHT
	| BRACKETLEFT BRACKETRIGHT
	| BRACKETLEFT constant_expression BRACKETRIGHT
	| direct_abstract_declarator BRACKETLEFT BRACKETRIGHT
	| direct_abstract_declarator BRACKETLEFT constant_expression BRACKETRIGHT
	| RBLEFT RBRIGHT
	| RBLEFT parameter_list RBRIGHT
	| direct_abstract_declarator RBLEFT RBRIGHT
	| direct_abstract_declarator RBLEFT parameter_list RBRIGHT
	;

initializer
	: assignment_expression
	| BRACELEFT initializer_list BRACERIGHT
	| BRACELEFT initializer_list COMMA BRACERIGHT
	;

initializer_list
	: initializer
	| initializer_list COMMA initializer
	;

statement
	: labeled_statement
	| compound_statement
	| expression_statement
	| selection_statement
	| iteration_statement
	| jump_statement
	;

labeled_statement
	: IDENTIFIER NUMBER_SIGN statement
	;

compound_statement
	: BRACELEFT BRACERIGHT
	| BRACELEFT statement_list BRACERIGHT
	| BRACELEFT declaration_list BRACERIGHT
	| BRACELEFT declaration_list statement_list BRACERIGHT
	;

declaration_list
	: declaration
	| declaration_list declaration
	;

statement_list
	: statement
	| statement_list statement
	;

expression_statement
	: SEMICOLON
	| expression SEMICOLON
	;

selection_statement
	: IF RBLEFT expression RBRIGHT statement ELSE statement
	;

iteration_statement
	: WHILE RBLEFT expression RBRIGHT statement
	| DO statement WHILE RBLEFT expression RBRIGHT SEMICOLON
	| FOR RBLEFT expression_statement expression_statement expression RBRIGHT statement
	;

jump_statement
	: GOTO IDENTIFIER SEMICOLON
	| BREAK SEMICOLON
	| RETURN SEMICOLON
	| RETURN expression SEMICOLON
	;

root
	: translation_unit
	;

translation_unit
	: external_declaration
	| translation_unit external_declaration
	;

external_declaration
	: function_definition
	| declaration
	;

function_definition
	: declaration_specifiers direct_declarator compound_statement
	;

%%

  private Yylex lexer;

  private int yylex () {
    int yyl_return = -1;
    try {
      yylval = new ParserVal(0);
      yyl_return = lexer.yylex();
    }
    catch (IOException e) {
      System.err.println("IO error :"+e);
    }
    return yyl_return;
  }


  public void yyerror (String error) {
    System.err.println ("Error: " + error);
  }

  public Parser(Reader r) {
    lexer = new Yylex(r, this);
  }

  public static String getTokenName(short c)  throws TokenNotFoundException
  {
      String val = "ERROR";
      if(c<Parser.yyname.length)
        val =  Parser.yyname[c];
      else
        throw new TokenNotFoundException();
      return val;
  }

  public static ParserVal ParseFile(String file) throws IOException {
    System.out.println("==========Lexical analyzer output=========");
	Parser yyparser;
    yyparser = new Parser(new FileReader(file));
    //Tokenize input file (for debug)
	Yylex lexer = new Yylex(new FileReader(file));
	    int i = 1;
          while(i>0)
          {
              i = lexer.yylex();
              System.out.print(Parser.yyname[i]);
              System.out.print(" ");
          }
	System.out.println();
    System.out.println("================Syntax tree===============");

  	//yyparser.yydebug = true;
  	yyparser.yyparse(); //Parsing goes here
  	return yyparser.yyval;
  }