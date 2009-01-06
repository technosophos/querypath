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
   * Remove the named attribute from all elements in the current QueryPath.
   *
   * @param string $name
   *  Name of the parameter to remove.
   * @return QueryPath
   *  The QueryPath object with the same elements.
   */
  public function removeAttr($name);
  
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
   * Append the current elements to the destination passed into the function.
   *
   * This cycles through all of the current matches and appends them to 
   * the context given in $destination. If a selector is provided then the 
   * $destination is queried (using that selector) prior to the data being
   * appended. The data is then appended to the found items.
   *
   * @param QueryPath $destination
   *  A QueryPath object that will be appended to.
   * @return QueryPath
   *  The original QueryPath, unaltered. Only the destination QueryPath will
   *  be modified.
   */
  public function appendTo(QueryPath $destination);
  
  /**
   * Insert the contents of the current QueryPath after the nodes in the 
   * destination QueryPath object.
   *
   * @param QueryPath $dest
   *  Destination object where the current elements will be deposited.
   * @return QueryPath
   *  The present QueryPath, unaltered. Only the destination object is altered.
   * @see after()
   * @see insertBefore()
   * @see append()
   */
  public function insertAfter(QueryPath $dest);
  
  /**
   * Insert the given data after each element in the current QueryPath object.
   *
   * This inserts the element as a peer to the currently matched elements.
   * Contrast this with {@link append()}, which inserts the data as children
   * of matched elements.
   *
   * @param mixed $data
   *  The data to be appended.
   * @return QueryPath
   *  The QueryPath object (with the items inserted).
   * @see before()
   * @see append()
   */
  public function after($data);
  /**
   * Insert the current elements into the destination document.
   * The items are inserted before each element in the given QueryPath document.
   * That is, they will be siblings with the current elements.
   *
   * @param QueryPath $dest
   *  Destination QueryPath document.
   * @return QueryPath
   *  The current QueryPath object, unaltered. Only the destination QueryPath
   *  object is altered.
   * @see before()
   * @see insertAfter()
   * @see appendTo()
   */
  public function insertBefore(QueryPath $dest);
  /**
   * Insert the given data before each element in the current set of matches.
   * 
   * @param mixed $data
   *  The data to be inserted. This can be XML in a string, a DomFragment, a DOMElement,
   *  or the other usual suspects. (See {@link qp()}).
   * @return QueryPath
   *  Returns the QueryPath with the new modifications. The list of elements currently
   *  selected will remain the same.
   */
  public function before($data);
  
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
   * Take all nodes in the current object and prepend them to the children nodes of
   * each matched node in the passed-in QueryPath object.
   *
   * This will iterate through each item in the current QueryPath object and 
   * add each item to the beginning of the children of each element in the 
   * passed-in QueryPath object.
   *
   * @see prepend()
   * @see appendTo()
   * @param QueryPath $dest
   *  The destination QueryPath object.
   * @return QueryPath
   *  The original QueryPath, unmodified. NOT the destination QueryPath.
   */
  public function prependTo(QueryPath $dest);
  
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
   * item. This works by first determining the parent element of the first item
   * in the list. It then moves all of the matching elements under the wrapper
   * and inserts the wrapper where that first element was found. (This is in 
   * accordance with the way jQuery works.)
   *
   * Markup is usually XML in a string, but it can also be a DOMNode, a document
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
   * Replace the existing element(s) in the list with a new one.
   *
   * @param mixed $new
   *  A DOMElement or XML in a string. This will replace all elements
   *  currently wrapped in the QueryPath object.
   * @return QueryPath
   *  The QueryPath object wrapping <b>the items that were removed</b>.
   *  This remains consistent with the jQuery API.
   */
  public function replaceWith($new);
  
  /**
   * Remove any items from the list if they match the selector.
   *
   * In other words, each item that matches the selector will be remove 
   * from the DOM document. The returned QueryPath wraps the list of 
   * removed elements.
   *
   * @param string $selector
   *  A CSS Selector.
   * @return QueryPath
   *  The Query path wrapping a list of removed items.
   */
  public function remove($selector = NULL);
  
  /**
   * This replaces everything that matches the selector with the first value
   * in the current list.
   *
   * This is the reverse of replaceWith.
   *
   * Unlike jQuery, QueryPath cannot assume a default document. Consequently,
   * you must specify the intended destination document. If it is omitted, the
   * present document is assumed to be tthe document. However, that can result
   * in undefined behavior if the selector and the replacement are not sufficiently
   * distinct.
   *
   * @param string $selector
   *  The selector.
   * @param DOMDocument $document
   *  The destination document.
   * @return QueryPath
   *  The QueryPath wrapping the modified document.
   * @deprecated Due to the fact that this is not a particularly friendly method,
   *  and that it can be easily replicated using {@see replaceWith()}, it is to be 
   *  considered deprecated.
   */
  public function replaceAll($selector, DOMDocument $document);
  
  /**
   * Add more elements to the current set of matches.
   *
   * This begins the new query at the top of the DOM again. The results found
   * when running this selector are then merged into the existing results. In
   * this way, you can add additional elements to the existing set.
   *
   * @param string $selector
   *  A valid selector.
   * @return QueryPath
   *  The QueryPath object with the newly added elements.
   */
  public function add($selector);
  
  /**
   * Revert to the previous set of matches.
   *
   * This will revert back to the last set of matches (before the last 
   * "destructive" set of operations). This undoes any change made to the set of
   * matched objects. Functions like {@see find()} and {@see filter()} change the 
   * list of matched objects. The end() function will revert back to the last set of
   * matched items.
   *
   * Note that functions that modify the document, but do not change the list of 
   * matched objects, are not "destructive". Thus, calling append('something')->end()
   * will not undo the append() call.
   *
   * Only one level of changes is stored. Reverting beyond that will result in 
   * an empty set of matches. Example:
   * <code>
   * // The line below returns the same thing as qp(document, 'p');
   * qp(document, 'p')->find('div')->end();
   * // This returns an empty array:
   * qp(document, 'p')->end();
   * // This returns an empty array:
   * qp(document, 'p')->find('div')->find('span')->end()->end();
   * </code>
   *
   * The last one returns an empty array because only one level of changes is stored.
   *
   * @return QueryPath
   *  A QueryPath object reflecting the list of matches prior to the last destructive
   *  operation.
   */
  public function end();
  
  /**
   * Combine the current and previous set of matched objects.
   *
   * Example:
   * <code>
   * qp(document, 'p')->find('div')->andSelf();
   * </code>
   * The code above will contain a list of all p elements and all div elements that 
   * are beneath p elements.
   *
   * @see end();
   * @return QueryPath
   *  A QueryPath object with the results of the last two "destructive" operations.
   */
  public function andSelf();
  
  /**
   * Remove all child nodes.
   *
   * This is equivalent to jQuery's empty() function. (However, empty() is a 
   * PHP built-in, and cannot be used as a method name.)
   *
   * @return QueryPath
   *  The QueryPath object with the child nodes removed.
   */
  public function removeChildren();
  
  /**
   * Get the children of the elements in the QueryPath object.
   *
   * If a selector is provided, the list of children will be filtered through
   * the selector.
   *
   * @param string $selector
   *  A valid selector.
   * @return QueryNode
   *  A QueryNode wrapping all of the children.
   */
  public function children($selector = NULL);
  
  /**
   * Get all child nodes (not just elements) of all items in the matched set.
   *
   * It gets only the immediate children, not all nodes in the subtree.
   *
   * This does not process iframes. Xinclude processing is dependent on the 
   * DOM implementation and configuration.
   *
   * @return QueryPath
   *  A QueryPath object wrapping all child nodes for all elements in the 
   *  QueryPath object.
   */
  public function contents();
  
  /**
   * Set or get the markup for an element.
   * 
   * If $markup is set, then the giving markup will be injected into each
   * item in the set. All other children of that node will be deleted, and this
   * new code will be the only child or children. The markup MUST BE WELL FORMED.
   *
   * If no markup is given, this will return a string representing the child 
   * markup of the first node.
   *
   * <b>Important:</b> This differs from jQuery's html() function. This function
   * returns <i>the current node</i> and all of its children. jQuery returns only
   * the children. This means you do not need to do things like this: 
   * <code>$qp->parent()->html()</code>.
   *
   * @param string $markup
   *  The text to insert.
   * @return mixed
   *  A string if no markup was passed, or a QueryPath if markup was passed.
   * @see xml()
   * @see text()
   */
  public function html($markup = NULL);
  
  public function text($text = NULL);
  
  /**
   * Set or get the XML markup for an element or elements.
   *
   * Like {@link html()}, this functions in both a setter and a getter mode.
   * 
   * In setter mode, the string passed in will be parsed and then appended to the 
   * elements wrapped by this QueryPath object.When in setter mode, this parses 
   * the XML using the DOMFragment parser. For that reason, an XML declaration 
   * is not necessary.
   *
   * In getter mode, the first element wrapped by this QueryPath object will be 
   * converted to an XML string and returned.
   *
   * @param string $markup
   *  A string containing XML data.
   * @return mixed
   *  If markup is passed in, a QueryPath is returned. If no markup is passed
   *  in, XML representing the first matched element is returned.
   * @see html()
   * @see text()
   */
  public function xml($markup = NULL);
  
  /**
   * Send the XML document to the client.
   * 
   * Write the document to stdout (usually the client).
   *
   * This prints the entire document.
   *
   * @return QueryPath
   *  The QueryPath object, unmodified.
   */
  public function writeXML();
  
  /**
   * Send the HTML to the client.
   * 
   * Write the document to stdout (usually the client).
   * @return QueryPath
   *  The QueryPath object, unmodified.
   */
  public function writeHTML();
    
  /**
   * Set or get the value of an element's 'value' attribute.
   *
   * The 'value' attribute is common in HTML form elements. This is a 
   * convenience function for accessing the values. Since this is not  common
   * task on the server side, this method may be removed in future releases. (It 
   * is currently provided for jQuery compatibility.)
   *
   * If a value is provided in the params, then the value will be set for all 
   * matches. If no params are given, then the value of the first matched element
   * will be returned. This may be NULL.
   *
   * @deprecated Just use attr(). There's no reason to use this on the server.
   * @see attr()
   * @param string $value
   * @return mixed
   *  Returns a QueryPath if a string was passed in, and a string if no string
   *  was passed in. In the later case, an error will produce NULL.
   */
  public function val($value = NULL);
  
  /**
   * Get a list of siblings for elements currently wrapped by this object.
   *
   * This will compile a list of every sibling of every element in the 
   * current list of elements. 
   *
   * Note that if two siblings are present in the QueryPath object to begin with,
   * then both will be returned in the matched set, since they are siblings of each 
   * other. In other words,if the matches contain a and b, and a and b are siblings of 
   * each other, than running siblings will return a set that contains 
   * both a and b.
   *
   * @param string $selector
   *  If the optional selector is provided, siblings will be filtered through
   *  this expression.
   * @return QueryPath
   *  The QueryPath containing the matched siblings.
   */
  public function siblings($selector = NULL);
  
  public function next();
  public function nextAll();
  public function parent($selector = NULL);
  public function parents($selector = NULL);
  public function prev();
  public function prevAll();
  
  
  
  


  
  public function clear();
  
  /**
   * Add a class to all elements in the current QueryPath.
   *
   * This searchers for a class attribute on each item wrapped by the current 
   * QueryPath object. If no attribute is found, a new one is added and its value
   * is set to $class. If a class attribute is found, then the value is appended
   * on to the end.
   *
   * @param string $class 
   *  The name of the class.
   * @return QueryPath
   *  Returns the QueryPath object.
   */
  public function addClass($class);
  /**
   * Remove the named class from any element in the QueryPath that has it.
   *
   * This may result in the entire class attribute being removed. If there
   * are other items in the class attribute, though, they will not be removed.
   * 
   * Example:
   * Consider this XML:
   * <code>
   * <element class="first second"/>
   * </code>
   *
   * Executing this fragment of code will remove only the 'first' class:
   * <code>
   * qp(document, 'element')->removeClass('first');
   * </code>
   *
   * The resulting XML will be:
   * <code>
   * <element class="second"/>
   * </code>
   *
   * To remove the entire 'class' attribute, you should use {@see removeAttr()}.
   *
   * @param string $class
   *  The class name to remove.
   * @return QueryPath
   *  The modified QueryPath object.
   */
  public function removeClass($class);
  /**
   * Returns TRUE if any of the elements in the QueryPath have the specified class.
   *
   * @param string $class
   *  The name of the class.
   * @return boolean 
   *  TRUE if the class exists in one or more of the elements, FALSE otherwise.
   */
  public function hasClass($class);
  
  public function cloneE();
  public function serialize();
  public function serializeArray();
  
}

/**
 * Exception indicating that a problem has occured inside of a QueryPath object.
 */
class QueryPathException extends Exception {}