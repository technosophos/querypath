<?php
/**
 * @file
 *
 */

/**
 *
 */
class QueryPath {
  /**
   * The version string for this version of QueryPath.
   *
   * Standard releases will be of the following form: <MAJOR>.<MINOR>[.<PATCH>][-STABILITY].
   *
   * Examples:
   * - 2.0
   * - 2.1.1
   * - 2.0-alpha1
   *
   * Developer releases will always be of the form dev-<DATE>.
   *
   * @since 2.0
   */
  const VERSION = '-UNSTABLE%';

  /**
   * This is a stub HTML 4.01 document.
   *
   * <b>Using {@link QueryPath::XHTML_STUB} is preferred.</b>
   *
   * This is primarily for generating legacy HTML content. Modern web applications
   * should use {@link QueryPath::XHTML_STUB}.
   *
   * Use this stub with the HTML familiy of methods ({@link html()},
   * {@link writeHTML()}, {@link innerHTML()}).
   */
  const HTML_STUB = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
  <html lang="en">
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title>Untitled</title>
  </head>
  <body></body>
  </html>';

  /**
   * This is a stub XHTML document.
   *
   * Since XHTML is an XML format, you should use XML functions with this document
   * fragment. For example, you should use {@link xml()}, {@link innerXML()}, and
   * {@link writeXML()}.
   *
   * This can be passed into {@link qp()} to begin a new basic HTML document.
   *
   * Example:
   * @code
   * $qp = qp(QueryPath::XHTML_STUB); // Creates a new XHTML document
   * $qp->writeXML(); // Writes the document as well-formed XHTML.
   * @endcode
   * @since 2.0
   */
  const XHTML_STUB = '<?xml version="1.0"?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml">
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>Untitled</title>
  </head>
  <body></body>
  </html>';


  public static function with($document, $selector = NULL, $options = array()) {
    $qpClass = isset($options['QueryPath_class']) ? $options['QueryPath_class'] : '\QueryPath\QueryPath';

    $qp = new $qpClass($document, $selector, $options);
    return $qp;
  }

  public static function withXML($source, $selector = NULL, $options = array()) {
    $options += array(
      'use_parser' => 'xml',
    );
    return self::with($document, $selector, $options);
  }

  public static function withHTML($source, $selector = NULL, $options = array()) {
    // Need a way to force an HTML parse instead of an XML parse when the
    // doctype is XHTML, since many XHTML documents are not valid XML
    // (because of coding errors, not by design).

    $options += array(
      'ignore_parser_warnings' => TRUE,
      'convert_to_encoding' => 'ISO-8859-1',
      'convert_from_encoding' => 'auto',
      //'replace_entities' => TRUE,
      'use_parser' => 'html',
      // This is stripping actually necessary low ASCII.
      //'strip_low_ascii' => TRUE,
    );
    return @self::with($document, $selector, $options);
  }

}
