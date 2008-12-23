#!/usr/bin/env php
<?php
require 'parser.php';
//$str = 'abc > def.g |: hi(jk)[lmn]*op';
//$str = '&abc.def';

print ord('"');
#$str = 'tag.class #id :test (test) + anotherElement > yetAnother[test] more[test="ing"]';
$str = 'tag.class #id :test (test)';
print "Now testing: $str\n";

$c = new DebugCssEventHandler();

$p = new CssParser($str, $c);
$p->parse();
