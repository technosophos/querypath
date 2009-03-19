#!/usr/bin/env php
<?php
/**
 * Generic CLI parser tests.
 *
 * These are not unit tests. They are just plain parser tests.
 * @package Tests
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
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
