<?php
/** @file
 * CSS selector parsing classes.
 *
 * This file contains the tools necessary for parsing CSS 3 selectors.
 * In the future it may be expanded to handle all of CSS 3.
 *
 * The parser contained herein is has an event-based API. Implementors should
 * begin by implementing the {@link EventHandler} interface. For an example
 * of how this is done, see {@link EventHandler.php}.
 *
 * @author M Butcher <matt@aleph-null.tv>
 * @license http://opensource.org/licenses/lgpl-2.1.php The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
namespace QueryPath\CSS;

/** @addtogroup querypath_css CSS Parsing
 * QueryPath includes a CSS 3 Selector parser.
 *
 *
 * Typically the parser is not accessed directly. Most developers will use it indirectly from
 * qp(), htmlqp(), or one of the methods on a QueryPath object.
 *
 * This parser is modular and is not tied to QueryPath, so you can use it in your
 * own (non-QueryPath) projects if you wish. To dive in, start with EventHandler, the
 * event interface that works like a SAX API for CSS selectors. If you want to check out
 * the details, check out the parser (CssParser),  scanner (CssScanner), and token list (CssToken).
 */

require_once 'EventHandler.php';


/**
 * An event handler for handling CSS 3 Selector parsing.
 *
 * This provides a standard interface for CSS 3 Selector event handling. As the
 * parser parses a selector, it will fire events. Implementations of EventHandler
 * can then handle the events.
 *
 * This library is inspired by the SAX2 API for parsing XML. Each component of a
 * selector fires an event, passing the necessary data on to the event handler.
 *
 * @ingroup querypath_css
 */
interface EventHandler {
  /** The is-exactly (=) operator. */
  const isExactly = 0; // =
  /** The contains-with-space operator (~=). */
  const containsWithSpace = 1; // ~=
  /** The contains-with-hyphen operator (!=). */
  const containsWithHyphen = 2; // |=
  /** The contains-in-string operator (*=). */
  const containsInString = 3; // *=
  /** The begins-with operator (^=). */
  const beginsWith = 4; // ^=
  /** The ends-with operator ($=). */
  const endsWith = 5; // $=
  /** The any-element operator (*). */
  const anyElement = '*';

  /**
   * This event is fired when a CSS ID is encountered.
   * An ID begins with an octothorp: #name.
   *
   * @param string $id
   *  The ID passed in.
   */
  public function elementID($id); // #name
  /**
   * Handle an element name.
   * Example: name
   * @param string $name
   *  The name of the element.
   */
  public function element($name); // name
  /**
   * Handle a namespaced element name.
   * example: namespace|name
   * @param string $name
   *  The tag name.
   * @param string $namespace
   *  The namespace identifier (Not the URI)
   */
  public function elementNS($name, $namespace = NULL);
  /**
   * Handle an any-element (*) operator.
   * Example: *
   */
  public function anyElement(); // *
  /**
   * Handle an any-element operator that is constrained to a namespace.
   * Example: ns|*
   * @param string $ns
   *  The namespace identifier (not the URI).
   */
  public function anyElementInNS($ns); // ns|*
  /**
   * Handle a CSS class selector.
   * Example: .name
   * @param string $name
   *  The name of the class.
   */
  public function elementClass($name); // .name
  /**
   * Handle an attribute selector.
   * Example: [name=attr]
   * Example: [name~=attr]
   * @param string $name
   *  The attribute name.
   * @param string $value
   *  The value of the attribute, if given.
   * @param int $operation
   *  The operation to be used for matching. See {@link EventHandler}
   *  constants for a list of supported operations.
   */
  public function attribute($name, $value = NULL, $operation = EventHandler::isExactly); // [name=attr]
  /**
   * Handle an attribute selector bound to a specific namespace.
   * Example: [ns|name=attr]
   * Example: [ns|name~=attr]
   * @param string $name
   *  The attribute name.
   * @param string $ns
   *  The namespace identifier (not the URI).
   * @param string $value
   *  The value of the attribute, if given.
   * @param int $operation
   *  The operation to be used for matching. See {@link EventHandler}
   *  constants for a list of supported operations.
   */
  public function attributeNS($name, $ns, $value = NULL, $operation = EventHandler::isExactly);
  /**
   * Handle a pseudo-class.
   * Example: :name(value)
   * @param string $name
   *  The pseudo-class name.
   * @param string $value
   *  The value, if one is found.
   */
  public function pseudoClass($name, $value = NULL); //:name(value)
  /**
   * Handle a pseudo-element.
   * Example: ::name
   * @param string $name
   *  The pseudo-element name.
   */
  public function pseudoElement($name); // ::name
  /**
   * Handle a direct descendant combinator.
   * Example: >
   */
  public function directDescendant(); // >
  /**
   * Handle a adjacent combinator.
   * Example: +
   */
  public function adjacent(); // +
  /**
   * Handle an another-selector combinator.
   * Example: ,
   */
  public function anotherSelector(); // ,
  /**
   * Handle a sibling combinator.
   * Example: ~
   */
  public function sibling(); // ~ combinator
  /**
   * Handle an any-descendant combinator.
   * Example: ' '
   */
  public function anyDescendant(); // ' ' (space) operator.

}

/**
 * Tokens for CSS.
 * This class defines the recognized tokens for the parser, and also
 * provides utility functions for error reporting.
 *
 * @ingroup querypath_css
 */
final class CssToken {
  const char = 0;
  const star = 1;
  const rangle = 2;
  const dot = 3;
  const octo = 4;
  const rsquare = 5;
  const lsquare = 6;
  const colon = 7;
  const rparen = 8;
  const lparen = 9;
  const plus = 10;
  const tilde = 11;
  const eq = 12;
  const pipe = 13;
  const comma = 14;
  const white = 15;
  const quote = 16;
  const squote = 17;
  const bslash = 18;
  const carat = 19;
  const dollar = 20;
  const at = 21; // This is not in the spec. Apparently, old broken CSS uses it.

  // In legal range for string.
  const stringLegal = 99;

  /**
   * Get a name for a given constant. Used for error handling.
   */
  static function name($const_int) {
    $a = array('character', 'star', 'right angle bracket',
      'dot', 'octothorp', 'right square bracket', 'left square bracket',
      'colon', 'right parenthesis', 'left parenthesis', 'plus', 'tilde',
      'equals', 'vertical bar', 'comma', 'space', 'quote', 'single quote',
      'backslash', 'carat', 'dollar', 'at');
    if (isset($a[$const_int]) && is_numeric($const_int)) {
      return $a[$const_int];
    }
    elseif ($const_int == 99) {
      return 'a legal non-alphanumeric character';
    }
    elseif ($const_int == FALSE) {
      return 'end of file';
    }
    return sprintf('illegal character (%s)', $const_int);
  }
}

