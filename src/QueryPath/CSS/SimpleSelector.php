<?php
/** @file
 *
 * A simple selector.
 *
 */

namespace QueryPath\CSS;

/**
 * Models a simple selector.
 *
 * CSS Selectors are composed of one or more simple selectors, where 
 * each simple selector may have any of the following components:
 *
 * - An element name (or wildcard *)
 * - An ID (#foo)
 * - One or more classes (.foo.bar)
 * - One or more attribute matchers ([foo=bar])
 * - One or more pseudo-classes (:foo)
 * - One or more pseudo-elements (::first)
 *
 * For performance reasons, this object has been kept as sparse as
 * possible.
 *
 * @since QueryPath 3.x
 * @author M Butcher
 *
 */
class SimpleSelector {

  const adjacent = 1;
  const directDescendant = 2;
  const anotherSelector = 4;
  const sibling = 8;
  const anyDescendant = 16;

  public $element = '*';
  public $ns;
  public $id;
  public $classes = array();
  public $attributes = array();
  public $pseudoClasses = array();
  public $pseudoElements = array();
  public $combinator;

  public function __construct() {
  }

}
