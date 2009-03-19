<?php
require_once '../src/QueryPath/QueryPath.php';

// The URL to look up:
//$url = 'http://dbpedia.org/data/The_Beatles.rdf';
$url = 'http://dbpedia.org/data/Swansea.rdf';
//$url = 'http://dbpedia.org/data/The_Lord_of_the_Rings.rdf';
// HTTP headers:
$headers = array(
  'Accept: application/rdf,application/rdf+xml;q=0.9,*/*;q=0.8',
  'Accept-Language: en-us,en',
  'Accept-Charset: ISO-8859-1,utf-8',
  'User-Agent: QueryPath/1.2',
);

// The context options:
$options = array(
  'http' => array(
    'method' => 'GET',
    'protocol_version' => 1.1,
    'header' => implode("\r\n", $headers),
  ),
);

$cxt = stream_context_create($options);

$qp = qp($url, 'rdf|Description', array('context' => $cxt));
//$qp = qp('The_Beatles.rdf');

printf("There are %d descriptions in this record.\n", $qp->size());

$qp->top()->find('rdf|*');
printf("There are %d RDF items in this record.\n", $qp->size());

print "About: " . $qp->top()->find('rdfs|label:first')->text() . PHP_EOL;
print "About (FOAF): " . $qp->top()->find('foaf|name:first')->text() . PHP_EOL;

print "\nComment:\n";
print $qp->top()->find('rdfs|comment[xml|lang="en"]')->text();
print PHP_EOL;

$qp->top();

print "\nImages:\n";
foreach ($qp->branch()->find('foaf|img') as $img) {
  print $img->attr('rdf:resource') . PHP_EOL;
}

print "\nImages Galleries:\n";
foreach ($qp->branch()->find('dbpprop|hasPhotoCollection') as $img) {
  print $img->attr('rdf:resource') . PHP_EOL;
}

print "\nOther Sites:\n";
foreach ($qp->branch()->find('foaf|page') as $img) {
  print $img->attr('rdf:resource') . PHP_EOL;
}

//$qp->writeXML();