<?php
require '../src/QueryPath/QueryPath.php';

// We are using the dbpedia database to execute a SPARQL query.

$url = 'http://dbpedia.org/sparql';
$sparql = 'select distinct ?Concept where {[] a ?Concept}';

$params = array(
  'query' => $sparql,
  'format' => 'application/sparql-results+xml',
  'default-graph-uri' => 'http://dbpedia.org',
);

$post = http_build_query($params);
$url .= '?' . $post;

print $url;
print file_get_contents($url);
/*
$content = file_get_contents($url);
print $content;

exit();
*/
#qp($url)->writeXML();