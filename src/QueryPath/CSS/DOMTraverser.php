<?php
/** @file
 * Traverse a DOM.
 */

namespace QueryPath\CSS;

class DOMTraverser implements Traverser {

  protected $matches = array();
  protected $selector;

  public function __construct($dom) {
  }

  public function find($selector) {
    $handler = new Selector();
    $parser = new Parser($selector, $handler);
    $parser->parse();
    $this->selector = $handler;
    $this->findMatches();
    return $this;
  }

  public function matches() {
    return $this->matches;
  }

  protected function findMatches() {

    // Order:
    // - element
    // - attribute
    // - id
    // - classes
    // - pseudoElement
    // - pseudoClass
    foreach ($this->selector->toArray() as $selector) {
      $this->matchElement($selector->element, $selector->ns);
      $this->matchAttributes($selector->attributes);
      $this->matchId($selector->id);
      $this->matchClasses($selector->classes);
      $this->matchPseudoClass($selector->pseudoClasses);
      $this->matchPseudoElements($selector->pseudoElements);
    }
  }

  protected function matchElement($element, $ns = NULL) {
    if (empty($element)) {
      return;
    }

    if (!empty($ns)) {
      throw new \Exception('FIXME: Need namespace support.');
    }

    $found = $this->newMatches();
    foreach ($this->getMatches() as $node) {
      $nl = $node->getElementByTagName($element);
      $this->attachNodeList($nl, $found);
    }
    $this->setMatches($found);
  }
  protected function matchAttributes($attributes) {
    foreach($attributes as $attr) {
      // FIXME
      if (isset($attr['ns'])) {
        throw new \Exception('FIXME: Attribute namespace support missing.');
      }
      $match = $this->newMatches();
      $nodes = $this->getMatches();
      foreach ($nodes as $node) {
        $name = $attr['name'];
        if ($node->hasAttribute($name)) {
          if (isset($attr['value'])) {
            $attrVal = $node->getAttribute($name);
            $this->matchAttributeValue($attr['value'], $node, $attr['op']);
          }
        }
      }
      $this->setMatches($match);
    }
  }
  /**
   * Check for attr value matches based on an operation.
   */
  protected function matchAttrbuteValue($needle, $haystack, $operation) {

    if (strlen($haystack) < strlen($needle)) return FALSE;

    // According to the spec:
    // "The case-sensitivity of attribute names in selectors depends on the document language."
    // (6.3.2)
    // To which I say, "huh?". We assume case sensitivity.
    switch ($operation) {
      case EventHandler::isExactly:
        return $needle == $haystack;
      case EventHandler::containsWithSpace:
        // XXX: This needs testing!
        return preg_match('/\b/', $haystack) == 1;
        //return in_array($needle, explode(' ', $haystack));
      case EventHandler::containsWithHyphen:
        return in_array($needle, explode('-', $haystack));
      case EventHandler::containsInString:
        return strpos($haystack, $needle) !== FALSE;
      case EventHandler::beginsWith:
        return strpos($haystack, $needle) === 0;
      case EventHandler::endsWith:
        //return strrpos($haystack, $needle) === strlen($needle) - 1;
        return preg_match('/' . $needle . '$/', $haystack) == 1;
    }
    return FALSE; // Shouldn't be able to get here.
  }
  protected function matchId($id) {
  }
  protected function matchClasses($classes) {
  }
  protected function matchPseudoClasses($pseudoClasses) {
  }
  protected function matchPseudoElements($pseudoElements) {
  }

  protected function newMatches() {
    return new SplObjectStorage();
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

}
