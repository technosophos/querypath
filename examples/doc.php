<?php
/**
 * Compare jQuery documentation to QueryPath documentation
 *
 * @package Examples
 * @author Emily Brand
 * @license LGPL The GNU Lesser GPL (LGPL) or an MIT-like license.
 * @see http://api.jquery.com/api/
 * 
 * TODO: make the page match querypath.org
 * TODO: the code the doesn't show up from querypath.xml
 */

require_once '../src/QueryPath/QueryPath.php';

/**
 * Add the link & class to each key to show in the left div.
 * 
 * @param String $v
 */
function addClasses($v) {
  return "<a href='".$_SERVER['PHP_SELF']."?key=$v'><span class='keyname'>$v</span></a><br />";
}

// The document skeleton
$qpdoc = htmlqp('doc.html', 'body');

$key = $_GET['key'];

// The jQuery categories that are used in QueryPath
$qparray = array('Tree Traversal', 'Child Filter', 'Attribute', 'Content Filter', 'Basic Filter', 
'Hierarchy', 'Basic', 'Filtering', 'Miscellaneous Traversing', 'DOM Insertion, Outside', 'DOM Insertion, Inside', 'Attributes');

$jqnames = array();
$qpnames = array();

// Search through the xml file to find any entries of jQuery entities
foreach(qp('querypath.xml', 'entry') as $entry) {
  $qpnames[$entry->attr('name')] =  
      array('jquery' => $entry->find('jquery')->text(), 
            'querypath' => $entry->parent()->find('querypath')->text());
}

// Search through the xml file to find all entries of jQuery entities
foreach(qp('http://api.jquery.com/api/', 'entry') as $entry) {
  if(array_search($entry->find('category')->attr('name'), $qparray)) {
    $jqnames[$entry->parent()->attr('name')] =  
      array('longdesc' => $entry->find('longdesc')->text(), 
            'name' => $entry->parent()->find('category')->attr('name'));
  }
}

// Map the keys & sort them
$jqkeys = array_keys($jqnames);
$jqkeys = array_map("addClasses", $jqkeys);
sort($jqkeys);

// Add the keys to the nav bar
$qpdoc->find('#leftbody');
foreach($jqkeys as $k => $v) {
  $qpdoc->append($v);
}

// Add the description to the main window if the key exists
if(array_key_exists($key, $jqnames)) {
  $qpdoc->parent()->find('#rightbody');
  if(array_key_exists($key, $qpnames)) {
    
    $qpdoc->append(htmlspecialchars($qpnames[$key]['jquery']));
    $qpdoc->append(htmlentities($qpnames[$key]['querypath'], TRUE));
  }
  else
    $qpdoc->append(htmlentities($jqnames[$key]['longdesc']));
}

// Write the document
$qpdoc->writeHTML();

