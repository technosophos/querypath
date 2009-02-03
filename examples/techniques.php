<?php
/**
 * This file shows five different ways to iterate through the contents of a
 * QueryPath.
 *
 * @see QueryPath
 * @package Examples
 * @author M Butcher <matt@aleph-null.tv>
 * @license LGPL (The GNU Lesser GPL) or an MIT-like license.
 */

require '../src/QueryPath/QueryPath.php';

$demo = '<?xml version="1.0" ?>
<data>
<li>Foo</li>
<li>Foo</li>
<li>Foo</li>
<li>Foo</li>
<li>Foo</li>
</data>
';

$qp = qp($demo, 'data');

// Iterate over elements as DOMNodes:
foreach ($qp->get() as $li_ele) {
  print $li->tagName . PHP_EOL; // Prints 'li' five times.
}

// Iterate over elements as QueryPath objects
foreach ($qp as $li_qp) {
  print $li->tag() . PHP_EOL; // Prints 'li' five times
}

function callbackFunction($index, $element) {
  print $element->tagName . PHP_EOL;
}

// Iterate using a callback function
$qp->each('callbackFunction');

// Iterate using a Lambda-style function
$qp->eachLambda('return $item->tagName . PHP_EOL;');

// Loop through by index/count
for ($i = 0; $i < $qp->size(); ++$i) {
  $domElement = $qp->get($i);
  print $domElement->tagName . PHP_EOL;
}