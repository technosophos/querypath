<?php
/**
 * Basic example of QueryPath usage.
 * @package Examples
 * @author M Butcher <matt@aleph-null.tv>
 * @license LGPL The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
require_once '../src/QueryPath/QueryPath.php';
qp(QueryPath::HTML_STUB)->find('body')->text('Hello World')->writeHTML();
?>
