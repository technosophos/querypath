<?php
/**
 * The Query Path package provides tools for manipulating a Document Object Model.
 * The two major DOMs are the XML DOM and the HTML DOM. Using Query Path, you can 
 * build, parse, search, and modify DOM documents.
 *
 * To use Query Path, this is the only file you should need to import.
 *
 * Standard usage:
 * <code>
 * $qp = qp('#myID', '<?xml?><test><foo id="myID"/></test>);
 * print $qp->append('<new><elements/></new>')->html();
 * </code>
 *
 * The above would print (formatted for readability):
 * <code>
 * <?xml version="1.0"?>
 * <test>
 *  <foo id="myID">
 *    <new>
 *      <element/>
 *    </new>
 *  </foo>
 * </test>
 * </code>
 *
 * @package QueryPath
 * @author M Butcher <matt @aleph-null.tv>
 * @license LGPL v2
 */
 
//define('QUICK_EXP', '/^[^<]*(<(.|\s)+>)[^>]*$|^#([\w-]+)$/');
/**
 * Regular expression for checking whether a string looks like XML.
 */
define('ML_EXP','/^[^<]*(<(.|\s)+>)[^>]*$/');

/**
 * The main implementation of the file is stored seprately from here.
 */
require_once 'QueryPathImpl.php';
/**
 * The CssEventHandler interfaces with the CSS parser.
 */
require_once 'CssEventHandler.php';
/**
 * The extender is used to provide support for extensions.
 */
//require_once 'QueryPathExtender.php';

/**
 * Build a new Query Path.
 * This builds a new Query Path object. The new object can be used for 
 * reading, search, and modifying a document.
 *
 * @param mixed $document
 *  A document in one of the following forms:
 *  - A string of XML or HTML
 *  - A path on the file system
 *  - A {@see DOMDocument} object
 *  - A {@see SimpleXMLElement} object.
 *  - A {@see DOMNode} object.
 * @param string $string 
 *  Either an XML/HTML string of data or a CSS 3 Selector.
 */
function qp($document, $string = NULL) {
  $qp = new QueryPathImpl($document, $string);
  // Do wrapping here...
  
  return $qp;
}
 
/**
 * The Query Path object is the primary tool in this library.
 * To create a new Query Path, use the {@see $dq()} function.
 */
interface QueryPath {
  /**
   * Given a CSS Selector, find matching items.
   *
   * @param string $selector
   *   CSS 3 Selector
   * @return QueryPath
   */
  public function find($selector);
  
  /**
   * Get the number of elements currently wrapped by this object.
   *
   * @return int
   *  Number of items in the object.
   */
  public function size();
  
  /**
   * Get one or all elements from this object.
   *
   * @param int $index
   *   If specified, then only this index value will be returned. If this 
   *   index is out of bounds, a NULL will be returned.
   * @return mixed
   *   If an index is passed, one element will be returned. If no index is
   *   present, an array of all matches will be returned.
   */
  public function get($index = NULL);
  
  /**
   * Reduce the matched set to just one.
   * @param $index
   *  The index of the element to keep. The rest will be 
   *  discarded.
   */
  public function eq($index);
  
  /**
   * Get/set an attribute.
   * - If both name and value are set, then this will set the attribute name/value
   *   pair for all items in this object. 
   * - If name is set, and is an array, then
   *   all attributes in the array will be set for all items in this object.
   * - If name is a string and is set, then the attribute value will be returned.
   *
   * When an attribute value is retrieved, only the attribute value of the FIRST
   * match is returned.
   *
   * @param mixed $name
   *   The name of the attribute or an associative array of name/value pairs.
   * @param string $value
   *   A value (used only when setting an individual property).
   * @return mixed
   *   If this was a setter request, return the QueryPath object. If this was
   *   an access request (getter), return the string value.
   */
  public function attr($name, $value = NULL);
  
  /**
   * Given a selector, this checks to see if the current set has one or more matches.
   *
   * Unlike jQuery's version, this supports full selectors (not just simple ones).
   *
   * @param string $selector
   *   The selector to search for.
   * @return boolean
   *   TRUE if one or more elements match. FALSE if no match is found.
   */
  public function is($selector);
  
  /**
   * Filter a list down to only elements that match the selector.
   * Use this, for example, to find all elements with a class, or with 
   * certain children.
   *
   * @param string $selector
   *   The selector to use as a filter.
   * @return QueryPath
   *   The QueryPath with non-matching items filtered out.
   */
  public function filter($selector);
  
  /**
   * Filter based on a lambda function.
   *
   * The function string will be executed as if it were the body of a 
   * function. It is passed two arguments:
   * - $index: The index of the item.
   * - $item: The current Element.
   * If the function returns boolean FALSE, the item will be removed from
   * the list of elements. Otherwise it will be kept.
   *
   * Example:
   * <code>
   * qp('li')->filterLambda('qp($item)->attr("id") == "test"');
   * </code>
   *
   * The above would filter down the list to only an item whose ID is
   * 'text'.
   *
   * @param string $function
   *  Inline lambda function in a string.
   */
  public function filterLambda($function);
  
  /**
   * Filter based on a callback function.
   *
   * A callback may be any of the following:
   *  - a function: 'my_func'.
   *  - an object/method combo: $obj, 'myMethod'
   *  - a class/method combo: 'MyClass', 'myMethod'
   * Note that classes are passed in strings. Objects are not.
   *
   * Each callback is passed to arguments:
   *  - $index: The index position of the object in the array.
   *  - $item: The item to be operated upon.
   *
   * @param $callback.
   *   A callback either as a string (function) or an array (object, method OR 
   *   classname, method).
   * @return QueryPath
   *   Query path object augmented according to the function.
   */
  public function filterCallback($callback);

  /**
   * Filter a list to contain only items that do NOT match.
   *
   * @param string $selector
   *  A selector to use as a negation filter. If the filter is matched, the 
   *  element will be removed from the list.
   * @return QueryPath
   *  The QueryPath object with matching items filtered out.
   */
  public function not($selector);
  
  /**
   * Get an item's index.
   *
   * Given a DOMElement, get the matching object from the 
   * matches.
   *
   * @param DOMElement $subject
   *  The item to match.
   * 
   * @return mixed
   *  The index as an integer (if found), or boolean FALSE. Since 0 is a 
   *  valid index, you should use strong equality (===) to test..
   */
  public function index($subject);
  
  /**
   * Run a function on each item in a set.
   *
   * The mapping callback can return anything. Whatever it returns will be
   * stored as a match in the set, though. This means that afer a map call, 
   * there is no guarantee that the elements in the set will behave correctly
   * with other QueryPath functions.
   *
   * Callback rules:
   * - If the callback returns NULL, the item will be removed from the array.
   * - If the callback returns an array, the entire array will be stored in 
   *   the results.
   * - If the callback returns anything else, it will be appended to the array 
   *   of matches.
   *
   * @param callback $callback
   *  The function or callback to use. The callback will be passed two params:
   *  - $index: The index position in the list of items wrapped by this object.
   *  - $item: The current item.
   *
   * @return QueryPath
   *  The QueryPath object wrapping a list of whatever values were returned
   *  by each run of the callback.
   *
   * @see QueryPath::get()
   */
  public function map($callback);

  /**
   * Narrow the items in this object down to only a slice of the starting items.
   *
   * @param integer $start
   *  Where in the list of matches to begin the slice.
   * @param integer $count
   *  The number of items to include in the slice. If nothing is specified, the 
   *  all remaining matches (from $start onward) will be included in the sliced
   *  list.
   * @see array_slice()
   */
  public function slice($start, $end = NULL);
  
  /**
   * Run a callback on each item in the list of items.
   *
   * Rules of the callback:
   * - A callback is passed to variables: $index and $item. (There is no 
   *   special treatment of $this, as there is in jQuery.)
   *   - Typically, you will want to pass $item by reference.
   * - A callback that returns FALSE will stop execution of the each() loop. This
   *   works like break in a standard loop.
   * - A TRUE return value from the callback is analogous to a continue statement.
   * - All other return values are ignored.
   *
   * @param callback $callback
   *  The callback to run.
   * @return QueryPath
   *  The QueryPath.
   */
  public function each($callback);
  
  /**
   * An each() iterator that takes a lambda function.
   * 
   * @param string $lambda
   *  The lambda function. This will be passed ($index, &$item).
   * @return QueryPath.
   *  The QueryPath object.
   */
  public function eachLambda($lambda);
  
  /**
   * Insert the given markup as the last child.
   *
   * The markup will be inserted into each match in the set.
   *
   * @param mixed $apendage
   *  This can be either a string (the usual case), or a DOM Element.
   */
  public function append($apendage);
  
  /**
   * Insert the given markup as the first child.
   *
   * The markup will be inserted into each match in the set.
   *
   * @param mixed $prependage
   *  This can be either a string (the usual case), or a DOM Element.
   */
  public function prepend($prependage);
  
  /**
   * Reduce the set of matches to the deepest child node in the tree.
   *
   * This loops through the matches and looks for the deepest child node of all of 
   * the matches. "Deepest", here, is relative to the nodes in the list. It is 
   * calculated as the distance from the starting node to the most distant child
   * node. In other words, it is not necessarily the farthest node from the root
   * element, but the farthest note from the matched element.
   *
   * In the case where there are multiple nodes at the same depth, all of the 
   * nodes at that depth will be included.
   *
   * @return QueryPath
   *  The QueryPath wrapping the single deepest node.
   */
  public function deepest();
  
  /**
   * Wrap each element inside of the given markup.
   *
   * Markup is usually a string, but it can also be a DOMNode, a document
   * fragment, a SimpleXMLElement, or another QueryPath object (in which case
   * the first item in the list will be used.)
   *
   * @param mixed $markup 
   *  Markup that will wrap each element in the current list.
   * @return QueryPath
   *  The QueryPath object with the wrapping changes made.
   */
  public function wrap($markup);
  /**
   * Wrap all elements inside of the given markup.
   *
   * So all elements will be grouped together under this single marked up 
   * item.
   *
   * Markup is usually a string, but it can also be a DOMNode, a document
    * fragment, a SimpleXMLElement, or another QueryPath object (in which case
    * the first item in the list will be used.)
    * 
   * @param string $markup 
   *  Markup that will wrap all elements in the current list.
   * @return QueryPath
   *  The QueryPath object with the wrapping changes made.
   */
  public function wrapAll($markup);
  /**
   * Wrap the child elements of each item in the list with the given markup.
   *
   * Markup is usually a string, but it can also be a DOMNode, a document
   * fragment, a SimpleXMLElement, or another QueryPath object (in which case
   * the first item in the list will be used.)
   *
   * @param string $markup 
   *  Markup that will wrap children of each element in the current list.
   * @return QueryPath
   *  The QueryPath object with the wrapping changes made.
   */
  public function wrapInner($element);
  
  /**
   * The tag name of the first element in the list.
   */
  public function tag();
  
  /**
   * Set or get the markup for an element.
   * 
   * If $markup is set, then the giving markup will be injected into each
   * item in the set. All other children of that node will be deleted, and this
   * new code will be the only child or children.
   *
   * If no markup is given, this will return a string representing the child 
   * markup of the first node.
   *
   * @param string $markup
   *  The text to insert.
   * @return mixed
   *  A string if no markup was passed, or a QueryPath if markup was passed.
   */
  public function html($markup = NULL);
  public function text($text = NULL);
  public function val();
  public function xml($markup = NULL);
  
  public function end();
  public function andSelf();
  
  public function add();
  public function children();
  public function siblings();
  public function contents();
  public function next();
  public function nextAll();
  public function parent();
  public function parents();
  public function prev();
  public function prevAll();
  
  
  public function appendTo($something);
  public function prependTo($something);
  public function insertAfter($something);
  public function after($something);
  public function insertBefore($something);
  public function before($something);

  
  public function clear();
  public function removeAll($selector);
  public function replaceWith($something);
  public function replaceAll($selector);
  
  
  public function remoteAttr($name);
  public function addClass($class);
  public function removeClass($class);
  public function hasClass($class);
  
  public function cloneE();
  public function serialize();
  public function serializeArray();
  
}

/**
 * Exception indicating that a problem has occured inside of a QueryPath object.
 */
class QueryPathException extends Exception {}