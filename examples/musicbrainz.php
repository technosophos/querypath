<?php
require_once '../src/QueryPath/QueryPath.php';

$artist_url = 'http://musicbrainz.org/ws/1/artist/?type=xml&name=u2';
$album_url = 'http://musicbrainz.org/ws/1/release/?type=xml&artistid=';
try {
  $artist = qp($artist_url, 'artist:first');
  if ($artist->size() > 0) {
    $id = $artist->attr('id');
    print 'The best match we found was for ' . $artist->children('name')->text() . PHP_EOL;
    print 'Artist ID: ' . $id . PHP_EOL;
    print 'Albums for this artist' . PHP_EOL;
    print $album_url . urlencode($id);
    $albums = qp($album_url . urlencode($id))->writeXML();
    
    foreach ($albums as $album) {
      print $album->find('title')->text() . PHP_EOL;
      // Fixme: Label is broken. See Drupal QueryPath module.
      print '(' . $album->next('label')->text() . ')' .PHP_EOL;
    }
  }
}
catch (Exception $e) {
  print $e->getMessage();
}
