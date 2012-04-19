<?php
/**
 * @file
 *
 * PseudoClass class.
 *
 * This is the first pass in an experiment to break PseudoClass handling
 * out of the normal traversal. Eventually, this should become a
 * top-level pluggable registry that will allow custom pseudoclasses.
 * For now, though, we just handle the core pseudoclasses.
 */
namespace QueryPath\CSS\DOMTraverser;

use \QueryPath\CSS\NotImplementedException;
use \QueryPath\CSS\EventHandler;
/**
 *  The PseudoClass handler.
 *
 */
class PseudoClass {

  /**
   * Tests whether the given element matches the given pseudoclass.
   *
   * @param string $pseudoclass
   *   The string name of the pseudoclass
   * @param resource $node
   *   The DOMNode to be tested.
   * @param resource $document
   *   The DOMDocument that is the active root for this node.
   * @param mixed $value
   *   The optional value string provided with this class. This is
   *   used, for example, in an+b psuedoclasses.
   * @retval boolean
   *   TRUE if the node matches, FALSE otherwise.
   */
  public function elementMatches($pseudoclass, $node, $document, $value = NULL) {
    $name = strtolower($pseudoclass);
    // Need to handle known pseudoclasses.
    switch($name) {
      case 'visited':
      case 'hover':
      case 'active':
      case 'focus':
      case 'animated': //  Last 3 are from jQuery
      case 'visible':
      case 'hidden':
        // These require a UA, which we don't have.
      case 'target':
        // This requires a location URL, which we don't have.
        return FALSE;
      case 'indeterminate':
        // The assumption is that there is a UA and the format is HTML.
        // I don't know if this should is useful without a UA.
        throw new NotImplementedException(":indeterminate is not implemented.");
        break;
      case 'lang':
        // No value = exception.
        if (!isset($value)) {
          throw new NotImplementedException(":lang() requires a value.");
        }
        return $this->lang($node, $value);
      case 'link':
        return Util::matchesAttribute($node, 'href');
        break;
      case 'root':
        return $node->isSameNode($node->ownerDocument->documentElement);

      // NON-STANDARD extensions for reseting to the "top" items set in
      // the constructor. Deprecated.
      case 'x-root':
      case 'x-reset':
        throw new NotImplementedExcetion("No longer supported.");
      // NON-STANDARD extensions for simple support of even and odd. These
      // are supported by jQuery, FF, and other user agents.
      case 'even':
        $this->nthChild(2, 0);
        break;
      case 'odd':
        $this->nthChild(2, 1);
        break;

      // Standard child-checking items.
      case 'nth-child':
        list($aVal, $bVal) = $this->parseAnB($value);
        $this->nthChild($aVal, $bVal);
        break;
      case 'nth-last-child':
        list($aVal, $bVal) = $this->parseAnB($value);
        $this->nthLastChild($aVal, $bVal);
        break;
      case 'nth-of-type':
        list($aVal, $bVal) = $this->parseAnB($value);
        $this->nthOfTypeChild($aVal, $bVal, FALSE);
        break;
      case 'nth-last-of-type':
        list($aVal, $bVal) = $this->parseAnB($value);
        $this->nthLastOfTypeChild($aVal, $bVal);
        break;
      case 'first-child':
        $this->nthChild(0, 1);
        break;
      case 'last-child':
        $this->nthLastChild(0, 1);
        break;

      case 'first-of-type':
        return $this->isFirstOfType($node);
      case 'last-of-type':
        return $this->isLastOfType($node);
      case 'only-of-type':
        return $this->isFirstOfType($node) && $this->isLastOfType($node);

      case 'not':
        if (empty($value)) {
          throw new ParseException(":not() requires a value.");
        }
        $this->not($value);
        break;
      // Additional pseudo-classes defined in jQuery:
      case 'lt':
      case 'gt':
      case 'nth':
      case 'eq':
      //case 'even':
      //case 'odd':
        $this->getByPosition($name, $value);
        break;
      case 'first':
        return $this->isFirst($node);
      case 'last':
        return $this->isLast($node);
      case 'only-child':
        return $this->isFirst($node) && $this->isLast($node);
        break;
      case 'empty':
        return $this->isEmpty($node);
      case 'parent':
        return !$this->isEmpty($node);

      case 'enabled':
      case 'disabled':
      case 'checked':
        return Util::matchesAttribute($node, $name);
      case 'text':
      case 'radio':
      case 'checkbox':
      case 'file':
      case 'password':
      case 'submit':
      case 'image':
      case 'reset':
      case 'button':
        return Util::matchesAttribute($node, 'type', $name);

      case 'header':
        return $this->header($node);
      case 'has':
        return $this->has($value);
        break;
      // Contains == text matches.
      // In QP 2.1, this was changed.
      case 'contains':
        return $this->contains($node, $value);
      // Since QP 2.1
      case 'contains-exactly':
        return $this->containsExactly($node, $value);
      default:
        throw new \QueryPath\CSS\ParseException("Unknown Pseudo-Class: " . $name);
    }
    $this->findAnyElement = FALSE;
  }

  /**
   * Pseudo-class handler for :lang
   *
   * Note that this does not implement the spec in its entirety because we do
   * not presume to "know the language" of the document. If anyone is interested
   * in making this more intelligent, please do so.
   */
  protected function lang($node, $value) {
    // TODO: This checks for cases where an explicit language is
    // set. The spec seems to indicate that an element should inherit
    // language from the parent... but this is unclear.
    $operator = (strpos($value, '-') !== FALSE) ? EventHandler::isExactly : EventHandler::containsWithHyphen;

    return Util::matchesAttribute($node, 'lang', $value, $operator)
      ||   Util::matchesAttributeNS($node, 'lang', 'xml', $value, $operator);
  }

  protected function header($node) {
    return preg_match('/^h[1-9]$/i', $node->tagName) == 1;
  }

  protected function isEmpty($node) {
    foreach ($node->childNodes as $kid) {
      // We don't want to count PIs and comments. From the spec, it
      // appears that CDATA is also not counted.
      if ($kid->nodeType == XML_ELEMENT_NODE || $kid->nodeType == XML_TEXT_NODE) {
        // As soon as we hit a FALSE, return.
        return FALSE;
      }
    }
    return TRUE;
  }

  protected function isFirst($node) {
    while (isset($node->previousSibling)) {
      $node = $node->previousSibling;
      if ($node->nodeType == XML_ELEMENT_NODE) {
        return FALSE;
      }
    }
    return TRUE;
  }
  protected function isFirstOfType($node) {
    $type = $node->tagName;
    while (isset($node->previousSibling)) {
      $node = $node->previousSibling;
      if ($node->nodeType == XML_ELEMENT_NODE && $node->tagName == $type) {
        return FALSE;
      }
    }
    return TRUE;
  }
  protected function isLast($node) {
    while (isset($node->nextSibling)) {
      $node = $node->nextSibling;
      if ($node->nodeType == XML_ELEMENT_NODE) {
        return FALSE;
      }
    }
    return TRUE;
  }
  protected function isLastOfType($node) {
    $type = $node->tagName;
    while (isset($node->nextSibling)) {
      $node = $node->nextSibling;
      if ($node->nodeType == XML_ELEMENT_NODE && $node->tagName == $type) {
        return FALSE;
      }
    }
    return TRUE;
  }
  protected function contains($node, $value) {
    $text = $node->textContent;
    $value = Util::removeQuotes($value);
    return isset($text) && (strpos($text, $value) !== FALSE);
  }
  protected function containsExactly($node, $value) {
    $text = $node->textContent;
    $value = Util::removeQuotes($value);
    return isset($text) && $text == $value;
  }

}
