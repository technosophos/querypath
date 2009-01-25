<?php
require_once 'src/QueryPath/QueryPath.php';
qp(QueryPath::HTML_STUB)->find('body')->text('Hello World')->writeHTML();
?>
