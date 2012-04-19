<?php
/** @file
 * Traverse a DOM.
 */

namespace QueryPath\CSS;

use \QueryPath\CSS\DOMTraverser\Util;
use \QueryPath\CSS\DOMTraverser\PseudoClass;
use \QueryPath\CSS\DOMTraverser\PseudoElement;

/**
 * Traverse a DOM, finding matches to the selector.
 *
 * This traverses a DOMDocument and attempts to find
 * matches to the provided selector.
 *
 * \b How this works
 *
 * This performs a bottom-up search. On the first pass,
 * it attempts to find all of the matching elements for the
 * last simple selector in a selector.
 *
 * Subsequent passes attempt to eliminate matches from the
 * initial matching set.
 *
 * Example:
 *
 * Say we begin with the selector `foo.bar baz`. This is processed
 * as follows:
 *
 * - First, find all baz elements.
 * - Next, for any baz element that does not have foo as an ancestor,
 *   eliminate it from the matches.
 * - Finally, for those that have foo as an ancestor, does that foo
 *   also have a class baz? If not, it is removed from the matches.
 *
 * \b Extrapolation
 *
 * Partial simple selectors are almost always expanded to include an
 * element.
 *
 * Examples:
 *
 * - `:first` is expanded to `*:first`
 * - `.bar` is expanded to `*.bar`.
 * - `.outer .inner` is expanded to `*.outer *.inner`
 *
 * The exception is that IDs are sometimes not expanded, e.g.:
 *
 * - `#myElement` does not get expanded
 * - `#myElement .class` \i may be expanded to `*#myElement *.class`
 *   (which will obviously not perform well).
 */
class DOMTraverser implements Traverser {

  protected $matches = array();
  protected $selector;
  protected $dom;
  protected $initialized = TRUE;

  /**
   * Build a new DOMTraverser.
   *
   * This requires a DOM-like object or collection of DOM nodes.
   */
  public function __construct($dom) {
    // This assumes a DOM. Need to also accomodate the case
    // where we get a set of elements.
    $this->initialized = FALSE;
    $this->dom = $dom;
    $this->matches = new \SplObjectStorage();
    $this->matches->attach($this->dom);
  }

  public function debug($msg) {
    fwrite(STDOUT, PHP_EOL . $msg);
  }

  /**
   * Given a selector, find the matches in the given DOM.
   *
   * This is the main function for querying the DOM using a CSS
   * selector.
   *
   * @param string $selector
   *   The selector.
   * @retval object SPLObjectStorage
   *   An SPLObjectStorage containing a list of matched
   *   DOMNode objects.
   */
  public function find($selector) {
    // Setup
    $handler = new Selector();
    $parser = new Parser($selector, $handler);
    $parser->parse();
    $this->selector = $handler;

    $selector = $handler->toArray();

    // Initialize matches if necessary.
    if (!$this->initialized) {
      $this->initialMatch($selector[0]);
      $this->initialized = TRUE;
    }

    $found = $this->newMatches();
    foreach ($this->matches as $candidate) {
      if ($this->matchesSelector($candidate, $selector)) {
        //$this->debug('Attaching ' . $candidate->nodeName);
        $found->attach($candidate);
      }
    }
    $this->setMatches($found);

    return $this;
  }

  public function matches() {
    return $this->matches;
  }

  /**
   * Check whether the given node matches the given selector.
   *
   * A selector is a group of one or more simple selectors combined
   * by combinators. This determines if a given selector
   * matches the given node.
   *
   * @attention
   * Evaluation of selectors is done recursively. Thus the length
   * of the selector is limited to the recursion depth allowed by
   * the PHP configuration. This should only cause problems for
   * absolutely huge selectors or for versions of PHP tuned to
   * strictly limit recursion depth.
   *
   * @param object DOMNode
   *   The DOMNode to check.
   * @param array Selector->toArray()
   *   The Selector to check.
   * @retval boolean
   *   A boolean TRUE if the node matches, false otherwise.
   */
  public function matchesSelector($node, $selector) {
    return $this->matchesSimpleSelector($node, $selector, 0);
  }

  /**
   * Performs a match check on a SimpleSelector.
   *
   * Where matchesSelector() does a check on an entire selector,
   * this checks only a simple selector (plus an optional
   * combinator).
   *
   * @param object DOMNode
   *   The DOMNode to check.
   * @param object SimpleSelector
   *   The Selector to check.
   * @retval boolean
   *   A boolean TRUE if the node matches, false otherwise.
   */
  public function matchesSimpleSelector($node, $selectors, $index) {
    $selector = $selectors[$index];
    // Note that this will short circuit as soon as one of these
    // returns FALSE.
    $result = $this->matchElement($node, $selector->element, $selector->ns)
      && $this->matchAttributes($node, $selector->attributes)
      && $this->matchId($node, $selector->id)
      && $this->matchClasses($node, $selector->classes)
      && $this->matchPseudoClasses($node, $selector->pseudoClasses)
      && $this->matchPseudoElements($node, $selector->pseudoElements);

    // If we have a match and we have a combinator, we need to
    // recurse up the tree.
    if ($result && isset($selectors[++$index])) {
      $result = $this->combine($node, $selectors, $index);
    }

    return $result;
  }

  /**
   * Combine the next selector with the given match
   * using the next combinator.
   *
   * If the next selector is combined with another
   * selector, that will be evaluated too, and so on.
   * So if this function returns TRUE, it means that all
   * child selectors are also matches.
   *
   * @param DOMNode $node
   *   The DOMNode to test.
   * @param array $selectors
   *   The array of simple selectors.
   * @param int $index
   *   The index of the current selector.
   * @retval boolean
   *   TRUE if the next selector(s) match.
   */
  public function combine($node, $selectors, $index) {
    $selector = $selectors[$index];
    //$this->debug(implode(' ', $selectors));
    switch ($selector->combinator) {
      case SimpleSelector::adjacent:
        return $this->combineAdjacent($node, $selectors, $index);
      case SimpleSelector::sibling:
        return $this->combineSibling($node, $selectors, $index);
      case SimpleSelector::directDescendant:
        return $this->combineDirectDescendant($node, $selectors, $index);
      case SimpleSelector::anyDescendant:
        return $this->combineAnyDescendant($node, $selectors, $index);
      ;
    }
    return FALSE;
  }

  /**
   * Process an Adjacent Sibling.
   *
   * The spec does not indicate whether Adjacent should ignore non-Element
   * nodes, so we choose to ignore them.
   *
   * @param DOMNode $node
   *   A DOM Node.
   * @param array $selectors
   *   The selectors array.
   * @param int $index
   *   The current index to the operative simple selector in the selectors
   *   array.
   * @return boolean
   *   TRUE if the combination matches, FALSE otherwise.
   */
  public function combineAdjacent($node, $selectors, $index) {
    while (!empty($node->previousSibling)) {
      $node = $node->previousSibling;
      if ($node->nodeType == XML_ELEMENT_NODE) {
        //$this->debug(sprintf('Testing %s against "%s"', $node->tagName, $selectors[$index]));
        return $this->matchesSimpleSelector($node, $selectors, $index);
      }
    }
    return FALSE;
  }

  /**
   * Check all siblings.
   *
   * According to the spec, this only tests elements LEFT of the provided
   * node.
   *
   * @param DOMNode $node
   *   A DOM Node.
   * @param array $selectors
   *   The selectors array.
   * @param int $index
   *   The current index to the operative simple selector in the selectors
   *   array.
   * @return boolean
   *   TRUE if the combination matches, FALSE otherwise.
   */
  public function combineSibling($node, $selectors, $index) {
    while (!empty($node->previousSibling)) {
      $node = $node->previousSibling;
      if ($node->nodeType == XML_ELEMENT_NODE && $this->matchesSimpleSelector($node, $selectors, $index)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Handle a Direct Descendant combination.
   *
   * Check whether the given node is a rightly-related descendant
   * of its parent node.
   *
   * @param DOMNode $node
   *   A DOM Node.
   * @param array $selectors
   *   The selectors array.
   * @param int $index
   *   The current index to the operative simple selector in the selectors
   *   array.
   * @return boolean
   *   TRUE if the combination matches, FALSE otherwise.
   */
  public function combineDirectDescendant($node, $selectors, $index) {
    $parent = $node->parentNode;
    if (empty($parent)) {
      return FALSE;
    }
    return $this->matchesSimpleSelector($parent, $selectors, $index);
  }

  /**
   * Handle Any Descendant combinations.
   *
   * This checks to see if there are any matching routes from the
   * selector beginning at the present node.
   *
   * @param DOMNode $node
   *   A DOM Node.
   * @param array $selectors
   *   The selectors array.
   * @param int $index
   *   The current index to the operative simple selector in the selectors
   *   array.
   * @return boolean
   *   TRUE if the combination matches, FALSE otherwise.
   */
  public function combineAnyDescendant($node, $selectors, $index) {
    while (!empty($node->parentNode)) {
      $node = $node->parentNode;

      // Catch case where element is child of something
      // else. This should really only happen with a
      // document element.
      if ($node->nodeType != XML_ELEMENT_NODE) {
        continue;
      }

      if ($this->matchesSimpleSelector($node, $selectors, $index)) {
        return TRUE;
      }
    }
  }

  /**
   * Get the intial match set.
   *
   * This should only be executed when not working with
   * an existing match set.
   */
  protected function initialMatch($selector) {
    $element = $selector->element;

    // If no element is specified, we have to start with the
    // entire document.
    if ($element == NULL) {
      $element = '*';
    }

    if (!empty($ns)) {
      throw new \Exception('FIXME: Need namespace support.');
    }

    $found = $this->newMatches();
    foreach ($this->getMatches() as $node) {
      $nl = $node->getElementsByTagName($element);
      $this->attachNodeList($nl, $found);
    }
    $this->setMatches($found);

    $selector->element = NULL;
  }

  /**
   * Checks to see if the DOMNode matches the given element selector.
   */
  protected function matchElement($node, $element, $ns = NULL) {
    if (empty($element)) {
      return TRUE;
    }

    if (!empty($ns)) {
      throw new \Exception('FIXME: Need namespace support.');
    }

    return $node->tagName == $element;

  }

  /**
   * Checks to see fi the given DOMNode matches an "any element" (*).
   */
  protected function matchAnyElement($node) {
    $ancestors = $this->ancestors($node);

    return count($ancestors) > 0;
  }

  /**
   * Get a list of ancestors to the present node.
   */
  protected function ancestors($node) {
    $buffer = array();
    $parent = $node;
    while (($parent = $parent->parentNode) !== NULL) {
      $buffer[] = $parent;
    }
    return $buffer;
  }

  /**
   * Check to see if DOMNode has all of the given attributes.
   */
  protected function matchAttributes($node, $attributes) {
    if (empty($attributes)) {
      return TRUE;
    }

    foreach($attributes as $attr) {
      // FIXME
      if (isset($attr['ns'])) {
        throw new \Exception('FIXME: Attribute namespace support missing.');
      }
      $val = isset($attr['value']) ? $attr['value'] : NULL;
      $matches = Util::matchesAttribute($node, $attr['name'], $val, $attr['op']);

      if (!$matches) {
        return FALSE;
      }
      /*
      $name = $attr['name'];
      if ($node->hasAttribute($name)) {
        if (isset($attr['value'])) {
          $attrVal = $node->getAttribute($name);
          $res = Util::matchesAttributeValue($attr['value'], $attrVal, $attr['op']);

          // As soon as we fail to match, return FALSE.
          if (!$res) {
            return FALSE;
          }
        }
      }
      // If the element doesn't have the attribute, fail the test.
      else {
        return FALSE;
      }
       */
    }
    return TRUE;
  }
  /**
   * Check that the given DOMNode has the given ID.
   */
  protected function matchId($node, $id) {
    if (empty($id)) {
      return TRUE;
    }
    return $node->hasAttribute('id') && $node->getAttribute('id') == $id;
  }
  /**
   * Check that the given DOMNode has all of the given classes.
   */
  protected function matchClasses($node, $classes) {
    if (empty($classes)) {
      return TRUE;
    }

    if (!$node->hasAttribute('class')) {
      return FALSE;
    }

    $eleClasses = preg_split('/\s+/', $node->getAttribute('class'));
    if (empty($eleClasses)) {
      return FALSE;
    }

    // The intersection should match the given $classes.
    $missing = array_diff($classes, array_intersect($classes, $eleClasses));

    return count($missing) == 0;
  }
  protected function matchPseudoClasses($node, $pseudoClasses) {
    return TRUE;
  }
  protected function matchPseudoElements($node, $pseudoElements) {
    return TRUE;
  }

  protected function newMatches() {
    return new \SplObjectStorage();
  }

  /**
   * Get the internal match set.
   * Internal utility function.
   */
  protected function getMatches() {
    return $this->matches();
  }

  /**
   * Set the internal match set.
   *
   * Internal utility function.
   */
  protected function setMatches($matches) {
    $this->matches = $matches;
  }

  /**
   * Attach all nodes in a node list to the given \SplObjectStorage.
   */
  public function attachNodeList(\DOMNodeList $nodeList, \SplObjectStorage $splos) {
    foreach ($nodeList as $item) $splos->attach($item);
  }

  public function getDocument() {
    return $this->dom;
  }

}
