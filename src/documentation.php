<?php
/** @mainpage QueryPath: Find Your Way
 * @image html querypath-200x333.png
 * QueryPath is a PHP library for working with XML and HTML. It is a PHP implementation of jQuery's
 * traversal and modification libraries.
 *
 * @section getting_started Getting Started
 *
 * To being using QueryPath, you will probably want to take a look at these three pieces of 
 * documentation:
 *  - qp(): The main QueryPath function (like jQuery's $ function.)
 *  - htmlqp(): A specialized version of qp() for dealing with poorly formatted HTML.
 *  - QueryPath: The QueryPath class, which has all of the main functions.
 *
 * One substantial difference from jQuery is that QueryPath does not return a new object for 
 * each call (for performance reasons). Instead, the same object is mutated from call to call.
 * A chain, then, typically performs all methods on the same object.
 * When you need multiple objects, QueryPath has a {@link QueryPath::branch()} function that 
 * will return a cloned QueryPath object.
 *
 * QueryPath also has numerous functions that jQuery does not. Some (like QueryPath::top() and 
 * QueryPath::dataURL()) are extensions we find useful.
 * Most, however, are to either emphasize PHP features (QueryPath::filterPreg()) or adapt to 
 * server-side needs (QueryPathEntities::replaceAllEntities()).
 *
 * @subsection basic_example A Few Basic Examples
 *
 * Here is a basic example of QueryPath usage:
 *
 * @code
 * require 'QueryPath/QueryPath.php';
 * 
 * qp('<?xml version="1.0"?><root><foo/></root>', 'foo')->append('<bar>baz</bar>')->writeXML();
 * @endcode
 *
 * The above will create a new document from the XML string, find the `foo` element, and then 
 * append the `bar` element (complete with its text). Finally, the call to QueryPath::writeXML() will
 * print the entire finished XML document to standard out (usually the web browser).
 *
 * Here's an example using htmlqp():
 *
 * @code
 * require 'QueryPath/QueryPath.php';
 * 
 * // URL to fetch:
 * $url = 'http://technosophos.com';
 *
 * print qp($url, 'title')->text();
 * @endcode
 *
 * The above will fetch the HTML from the given URL and then find the `title` tag. It will extract
 * the text (QueryPath::text()) from the title and print it.
 *
 * For more examples, check out the #Examples namespace (start with {@link examples/html.php}). Also, read about the 
 * qp() and htmlqp() functions.
 *
 * @subsection online_sources Online Sources
 *
 *   - The official QueryPath site http://querypath.org
 *   - The latest API docs http://api.querypath.org
 *   - IBM DeveloperWorks Intro to QueryPath http://www.ibm.com/developerworks/web/library/os-php-querypath/index.html 
 *   - QueryPath articles at TechnoSophos.Com http://technosophos.com/qp/articles
 *   - The QueryPath GitHub repository http://github.com/technosophos/querypath
 *
 * If you find a good online resource, please submit it as an issue in GitHub, and we will 
 * most likely add it here.
 *
 * @subsection more_examples A Larger Example
 *
 * @include examples/html.php
 * 
 * @page extensions Using and Writing Extensions
 *
 * Using an extension is as easy as including it in your code:
 * 
 * @code
 * <?php
 * require 'QueryPath/QueryPath.php';
 * require 'QueryPath/Extension/QPXML.php';
 *
 * // Now I have the QPXML methods available:
 * qp(QueryPath::HTML_STUB)->comment('This is an HTML comment.');
 * ?>
 * @endcode
 *
 * Like jQuery, QueryPath provides a simple mechanism for writing extensions.
 *
 * Check out QPXSL and QPXML for a few easy-to-read extensions. QPDB provides an example of
 * a more complex extension.
 *
 * QueryPathExtension is the master interface for all extensions.
 *
 */