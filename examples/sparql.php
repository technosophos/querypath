<?php
/**
 * Use QueryPath to query semantic servers.
 *
 * This demo shows how a more complex GET query can be built up in 
 * QueryPath. POST queries are supported, too. Use a stream context
 * to create those.
 */
require '../src/QueryPath/QueryPath.php';

// We are using the dbpedia database to execute a SPARQL query.

// URL to DB Pedia's SPARQL endpoint.
$url = 'http://dbpedia.org/sparql';

// The SPARQL query to run.
$sparql = 'select distinct ?Concept where {[] a ?Concept}';

// We first set up the parameters that will be sent.
$params = array(
  'query' => $sparql,
  'format' => 'application/sparql-results+xml',
  'default-graph-uri' => 'http://dbpedia.org',
);

// DB Pedia wants a GET query, so we create one.
$post = http_build_query($params);
$url .= '?' . $post;

// Next, we simply retrieve, parse, and output the contents.
// The content of this query is very large, and can take a long time to load.
//print file_get_contents($url);
qp($url)->writeXML();