<?php
/**
 * This file contains a full implementation of the CssEventHandler interface.
 * 
 * The tools in this package initiate a CSS selector parsing routine and then
 * handle all of the callbacks.
 *
 * The implementation provided herein adheres to the CSS 3 Selector specification
 * with the following caveats:
 *
 *  - The negation (:not()) and containment (:has()) pseudo-classes allow *full* 
 *    selectors and not just simple selectors.
 *  - There are a variety of additional pseudo-classes supported by this 
 *    implementation that are not part of the spec. Most of the jQuery 
 *    pseudo-classes are supported. The :x-root pseudo-class is also supported.
 *  - Pseudo-classes that require a User Agent to function have been disabled.
 *    Thus there is no :hover pseudo-class.
 *  - All pseudo-elements require the double-colon (::) notation. This breaks 
 *    backward compatibility with the 2.1 spec, but it makes visible the issue
 *    that pseudo-elements cannot be effectively used with most of the present 
 *    library. They return strings instead of elements.
 *  - The pseudo-classes first-of-type, nth-of-type and last-of-type may or may
 *    not conform to the specification. The spec is unclear.
 *  - pseudo-class filters of the form -an+b do not function as described in the
 *    specification. However, they do behave the same way here as they do in 
 *    jQuery.
 *  - This library DOES provide XML namespace aware tools. Selectors can use
 *    namespaces to increase specificity.
 *  - This library does nothing with the CSS 3 Selector specificity rating. Of 
 *    course specificity is preserved (to the best of our abilities), but there
 *    is no calculation done.
 *
 * For detailed examples of how the code works and what selectors are supported,
 * see the {@see CssEventTests.php} file, which contains the unit tests used for
 * testing this implementation.
 *
 * @package QueryPath
 * @subpackage CSSParser
 * @author M Butcher <matt@aleph-null.tv>
 * @license http://opensource.org/licenses/lgpl-2.1.php LGPL (The GNU Lesser GPL) or an MIT-like license.
 */

/**
 * Require the parser library.
 */
require_once 'CssParser.php';

/**
 * Handler that tracks progress of a query through a DOM.
 *
 * The main idea is that we keep a copy of the tree, and then use an
 * array to keep track of matches. To handle a list of selectors (using
 * the comma separator), we have to track both the currently progressing
 * match and the previously matched elements.
 *
 * To use this handler:
 * <code>
 * $filter = '#id'; // Some CSS selector
 * $handler = new QueryPathCssParser(DOMNode $dom);
 * $parser = new CssParser();
 * $parser->parse($filter, $handler);
 * $matches = $handler->getMatches();
 * </code>
 *
 * $matches will be an array of zero or more DOMElement objects.
 */
class QueryPathCssEventHandler implements CssEventHandler {
  protected $dom = NULL; // Always points to the top level.
  protected $matches = NULL; // The matches
  protected $alreadyMatched = array(); // Matches found before current selector.
  protected $findAnyElement = TRUE;
  
  
  /**
   * Create a new event handler.
   */
  public function __construct($dom) {
    // Array of DOMElements
    if (is_array($dom)) {
      $matches = array();
      foreach($dom as $item) {
        if ($item instanceof DOMNode && $item->nodeType == XML_ELEMENT_NODE) {
          $matches[] = $item;
        }
      }
      $this->dom = count($matches) > 0 ? $matches[0] : NULL;
      $this->matches = $matches;
    }
    // DOM Document -- we get the root element.
    elseif ($dom instanceof DOMDocument) {
      $this->dom = $dom->documentElement;
      $this->matches = array($dom->documentElement);
    }
    // DOM Element -- we use this directly
    elseif ($dom instanceof DOMElement) {
      $this->dom = $dom;
      $this->matches = array($dom);
    }
    // NodeList -- We turn this into an array
    elseif ($dom instanceof DOMNodeList) {
      $matches = array();
      foreach ($dom as $item) {
        if ($item->nodeType == XML_ELEMENT_NODE) {
          $matches[] = $item;
        }
      }
      $this->dom = $matches;
      $this->matches = $matches;
    }
    // FIXME: Handle SimpleXML!
    // Uh-oh... we don't support anything else.
    else {
      throw new Exception("Unhandled type: " . get_class($dom));
    }
  }
  
  /**
   * Generic finding method.
   *
   * This is the primary searching method used throughout QueryPath.
   *
   * @param string $filter
   *  A valid CSS 3 filter.
   * @return QueryPathCssEventHandler
   *  Returns itself.
   */
  public function find($filter) {
    $parser = new CssParser($filter, $this);
    $parser->parse();
    return $this;
  }
  
  /**
   * Get the elements that match the evaluated selector.
   *
   * This should be called after the filter has been parsed.
   *
   * @return array
   *  The matched items. This is almost always an array of 
   *  {@link DOMElement} objects. It is always an instance of
   *  {@link DOMNode} objects.
   */
  public function getMatches() {
    $result = array_merge($this->alreadyMatched, $this->matches);
    return $result;
  }
  
  /**
   * Find any element with the ID that matches $id.
   *
   * If this finds an ID, it will immediately quit. Essentially, it doesn't
   * enforce ID uniqueness, but it assumes it.
   *
   * @param $id
   *  String ID for an element.
   */
  public function elementID($id) {
    $found = array();
    $matches = $this->candidateList();
    foreach ($matches as $item) {
      // Check if any of the current items has the desired ID.
      if ($item->hasAttribute('id') && $item->getAttribute('id') === $id) {
        $found = array($item);
        break;
      }
    }
    $this->matches = $found;
    $this->findAnyElement = FALSE;
  }
  
  // Inherited
  public function element($name) {
    $matches = $this->candidateList();
    $this->findAnyElement = FALSE;
    $found = array();
    foreach ($matches as $item) {
      // Should the existing item be included?
      // In some cases (e.g. element is root element)
      // it definitely should. But what about other cases?
      if ($item->tagName == $name) {
        $found[] = $item;
      }
      // Search for matching kids.
      //$nl = $item->getElementsByTagName($name);
      //$found = array_merge($found, $this->nodeListToArray($nl));
    }
    
    $this->matches = UniqueElementList::get($found);
  }
  
  // Inherited
  public function elementNS($lname, $namespace = NULL) {
    $this->findAnyElement = FALSE;
    $found = array();
    $matches = $this->candidateList();
    foreach ($matches as $item) {
      // Looking up NS URI only works if the XMLNS attributes are declared
      // at a level equal to or above the searching doc. Normalizing a doc
      // should fix this, but it doesn't. So we have to use a fallback 
      // detection scheme which basically searches by lname and then 
      // does a post hoc check on the tagname.
      
      //$nsuri = $item->lookupNamespaceURI($namespace);
      $nsuri = $this->dom->lookupNamespaceURI($namespace);
      if (!empty($nsuri)) {
        $nl = $item->getElementsByTagNameNS($nsuri, $lname);
        // If something is found, merge them:
        if (!empty($nl)) $found = array_merge($found, $this->nodeListToArray($nl));
      }
      else {
        //$nl = $item->getElementsByTagName($namespace . ':' . $lname);
        $nl = $item->getElementsByTagName($lname);
        $tagname = $namespace . ':' . $lname;
        $nsmatches = array();
        foreach ($nl as $node) {
          if ($node->tagName == $tagname) {
            $nsmatches[] = $node;
          }
        }
        // If something is found, merge them:
        if (!empty($nsmatches)) $found = array_merge($found, $nsmatches);
      }
    }
    $this->matches = $found;
  }
  
  public function anyElement() {
    $found = array();
    $this->findAnyElement = TRUE;
    $matches = $this->candidateList();
    foreach ($matches as $item) {
      $found[] = $item; // Add self
      $nl = $item->getElementsByTagName('*');
      $found = array_merge($found, $this->nodeListToArray($nl));
    }
    
    $this->matches = UniqueElementList::get($found);
    $this->findAnyElement = FALSE;
  }
  public function anyElementInNS($ns) {
    $this->findAnyElement = TRUE;
    $nsuri = $this->dom->lookupNamespaceURI($ns);
    $found = array();
    if (!empty($nsuri)) {
      $matches = $this->candidateList();
      foreach ($matches as $item) {
        $nl = $item->getElementsByTagNameNS($nsuri, '*');
        if (!empty($nl)) $found = array_merge($found, $this->nodeListToArray($nl));
      }
    }
    $this->matches = UniqueElementList::get($found);
    $this->findAnyElement = FALSE;
  }
  public function elementClass($name) {
    
    $found = array();
    $matches = $this->candidateList();
    foreach ($matches as $item) {
      if ($item->hasAttribute('class')) {
        $classes = explode(' ', $item->getAttribute('class'));
        if (in_array($name, $classes)) $found[] = $item;
      }
    }
    
    $this->matches = UniqueElementList::get($found);
    $this->findAnyElement = FALSE;
  }
  
  public function attribute($name, $value = NULL, $operation = CssEventHandler::isExactly) {
    $found = array();
    $matches = $this->candidateList();
    foreach ($matches as $item) {
      if ($item->hasAttribute($name)) {
        if (isset($value)) {
          // If a value exists, then we need a match.
          if($this->attrValMatches($value, $item->getAttribute($name), $operation)) {
            $found[] = $item;
          }
        }
        else {
          // If no value exists, then we consider it a match.
          $found[] = $item;
        }
      }
    }
    $this->matches = UniqueElementList::get($found);
    $this->findAnyElement = FALSE;
  }

  /**
   * Helper function to find all elements with exact matches.
   * 
   * @deprecated All use cases seem to be covered by attribute().
   */
  protected function searchForAttr($name, $value = NULL) {
    $found = array();
    $matches = $this->candidateList();
    foreach ($matches as $candidate) {
      if ($candidate->hasAttribute($name)) {
        // If value is required, match that, too.
        if (isset($value) && $value == $candidate->getAttribute($name)) {
          $found[] = $candidate;
        }
        // Otherwise, it's a match on name alone.
        else {
          $found[] = $candidate;
        }
      }
    }
    
    $this->matches = $found;
  }
  
  public function attributeNS($lname, $ns, $value = NULL, $operation = CssEventHandler::isExactly) {
    $matches = $this->candidateList();
    $found = array();
    if (count($matches) == 0) {
      $this->matches = array();
      return;
    }
    
    // Get the namespace URI for the given label.
    $uri = $matches[0]->lookupNamespaceURI($ns);
    
    foreach ($matches as $item) {
      //foreach ($item->attributes as $attr) {
      //  print "$attr->prefix:$attr->localName ($attr->namespaceURI), Value: $attr->nodeValue\n";
      //}
      if ($item->hasAttributeNS($uri, $lname)) {
        if (isset($value)) {
          if ($this->attrValMatches($value, $item->getAttributeNS($uri, $lname), $operation)) {
            $found[] = $item;
          }
        }
        else {
          $found[] = $item;
        }
      }
    }
    $this->matches = UniqueElementList::get($found);
    $this->findAnyElement = FALSE;
  }
  
  /**
   * This also supports the following nonstandard pseudo classes:
   *  - :x-reset/:x-root (reset to the main item passed into the constructor. Less drastic than :root)
   *  - :odd/:even (shorthand for :nth-child(odd)/:nth-child(even))
   */
  public function pseudoClass($name, $value = NULL) {
    $name = strtolower($name);
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
        $this->matches = array();
        break;
      case 'indeterminate':
        // The assumption is that there is a UA and the format is HTML.
        // I don't know if this should is useful without a UA.
        throw new NotImplementedException(":indeterminate is not implemented.");
        break;
      case 'lang':
        // No value = exception.
        if (!isset($value)) {
          throw new NotImplementedException("No handler for lang pseudoclass without value.");
        }
        $this->lang($value);
        break;
      case 'link':
        $this->searchForAttr('href');
        break;
      case 'root':
        if (empty($this->dom)) {
          $this->matches = array();
        }
        elseif (is_array($this->dom)) {
          $this->matches = array($this->dom[0]->ownerDocument->documentElement);
        }
        elseif ($this->dom instanceof DOMNode) {
          $this->matches = array($this->dom->ownerDocument->documentElement);
        }
        elseif ($this->dom instanceof DOMNodeList && $this->dom->length > 0) {
          $this->matches = array($this->dom->item(0)->ownerDocument->documentElement);
        }
        else {
          // Hopefully we never get here:
          $this->matches = array($this->dom);
        }
        break;
      
      // NON-STANDARD extensions for reseting to the "top" items set in
      // the constructor.  
      case 'x-root':
      case 'x-reset':
        $this->matches = array($this->dom);
        break;        
      
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
        $this->nthOfTypeChild($aVal, $bVal);
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
        $this->firstOfType();
        break;
      case 'last-of-type':
        $this->lastOfType();
        break;
      case 'only-child':
        $this->onlyChild();
        break;
      case 'only-of-type':
        $this->onlyOfType();
        break;
      case 'empty':
        $this->emptyElement();
        break;  
      case 'not':
        if (empty($value)) {
          throw new CssParseException(":not() requires a value.");
        }
        $this->not($value);
        break;
      // Additional pseudo-classes defined in jQuery:
      case 'lt':
      case 'gt':
      case 'nth':
      case 'eq':
      case 'first':
      case 'last':
      //case 'even':
      //case 'odd':
        $this->getByPosition($name, $value);  
        break;
      case 'parent':
        $matches = $this->candidateList();
        $found = array();
        foreach ($matches as $match) {
          if (!empty($match->firstChild)) {
            $found[] = $match;
          }
        }
        $this->matches = UniqueElementList::get($found);
        break;
      
      case 'enabled':  
      case 'disabled':  
      case 'checked':  
        $this->attribute($name);
        break;
      case 'text':
      case 'radio':
      case 'checkbox':
      case 'file':
      case 'password':
      case 'submit':
      case 'image':
      case 'reset':
      case 'button':
      case 'submit':
        $this->attribute('type', $name);
        break;

      case 'header':
        $matches = $this->candidateList();
        $found = array();
        foreach ($matches as $item) {
          $tag = $item->tagName;
          $f = strtolower(substr($tag, 0, 1));
          if ($f == 'h' && strlen($tag) == 2 && ctype_digit(substr($tag, 1, 1))) {
            $found[] = $item;
          }
        }
        $this->matches = UniqueElementList::get($found);
        break;
      case 'has':
        $this->has($value);
        break;
      // Contains == text matches.
      case 'contains':
        $matches = $this->candidateList();
        $found = array();
        foreach ($matches as $item) {
          if ($item->textContent == $value) {
            $found[] = $item;
          }
        }
        $this->matches = $found;
        break;
      default:
        throw new CssParseException("Unknown Pseudo-Class: " . $name);
    }
    $this->findAnyElement = FALSE;
  }
  
  /**
   * Pseudo-class handler for a variety of jQuery pseudo-classes.
   * Handles lt, gt, eq, nth, first, last pseudo-classes.
   */
  private function getByPosition($operator, $pos) {
    $matches = $this->candidateList();
    $found = array();
    if (empty($matches)) {
      return;
    }
    
    switch ($operator) {
      case 'nth':
      case 'eq':
        if (count($matches) >= $pos) {
          $found[] = $matches[$pos -1];
        }
        break;
      case 'first':
        $found[] = $matches[0];
        break;
      case 'last':
        $found[] = $matches[count($matches) - 1];
        break;
      // case 'even': 
      //         for ($i = 1; $i <= count($matches); ++$i) {
      //           if ($i % 2 == 0) {
      //             $found[] = $matches[$i];
      //           }
      //         }
      //         break;
      //       case 'odd':
      //         for ($i = 1; $i <= count($matches); ++$i) {
      //           if ($i % 2 == 0) {
      //             $found[] = $matches[$i];
      //           }
      //         }
      //         break;
      case 'lt':
        for ($i = 1; $i <= count($matches); ++$i) {
          if ($i < $pos) {
            $found[] = $matches[$i];
          }
        }
        break;
      case 'gt':
        for ($i = 1; $i <= count($matches); ++$i) {
          if ($i > $pos) {
            $found[] = $matches[$i-1];
          }
        }
        break;
    }
    
    $this->matches = $found;
  }
  
  /**
   * Parse an an+b rule for CSS pseudo-classes.
   * @param $rule
   *  Some rule in the an+b format.
   * @return 
   *  Array (list($aVal, $bVal)) of the two values.
   * @throws CssParseException
   *  If the rule does not follow conventions.
   */
  protected function parseAnB($rule) {
    if ($rule == 'even') {
      return array(2, 0);
    }
    elseif ($rule == 'odd') {
      return array(2, 1);
    }
    elseif ($rule == 'n') {
      return array(1, 0);
    }
    elseif (is_numeric($rule)) {
      return array(0, (int)$rule);
    }
    
    $rule = explode('n', $rule);
    if (count($rule) == 0) {
      throw new CssParseException("nth-child value is invalid.");
    }
    $aVal = (int)trim($rule[0]);
    $bVal = !empty($rule[1]) ? (int)trim($rule[1]) : 0;
    return array($aVal, $bVal);
  }
  
  protected function nthChild($groupSize, $elementInGroup) {
    // BEGIN implementing jQuery algo for this:
    // This might be more compact, but it has at least one
    // E_STRICT violation (creating object properties on the fly),
    // and it may not be as fast in PHP, since most of the descent
    // processing is handled in PHP instead of in C.
    $merge = array();
    $tmp = array();
    $first = $groupSize;
    $last = $elementInGroup;
    $r = $this->matches;
    
    for($i = 0; $i < count($r); $i++) {
      $node = $r[$i];
      $parentNode = $node->parentNode;
      $id = md5(serialize($parentNode));
      $nodeIndex = NULL;
      
      if (empty($merge[$id])) {
        $c = 1;
        
        for ($n = $parentNode->firstChild; $n; $n = $n->nextSibling) {
          if ($n->nodeType == XML_ELEMENT_NODE) {
            $n->nodeIndex = $c++;
          }
        }
        
        $merge[$id] = TRUE;
      }
      
      $add = FALSE;
      
      if ($first == 0) {
        if ($node->nodeIndex == $last) {
          $add = TRUE;
        }
      }
      elseif (($node->nodeIndex - $last) % $first == 0 && ($node->nodeIndex - $last) / $first >= 0) {
        $add = TRUE;
      }
      
      if ($add/* ^ $not*/) {
        $tmp[] = $node;
      }
    }
    
    $this->matches = $tmp;
    // END jQuery algo
    return;
    /*
    // Orig:

    // If findAnyElement is true, then we 
    // know that there was no element selector
    // preceding this.
    $restrictToElement = !$this->findAnyElement;
        
    $found = array();
    $groupSize = abs($groupSize);
    if ($elementInGroup < 0) {
      // in an-b, element in group is effectively a-b.
      $elementInGroup = $groupSize + $elementInGroup;
    }
    if ($groupSize == 0) {
      if ($elementInGroup == 0 ) {
        // Both == 0 means no matches:
        $this->matches = array();
        return;
      }
      else {
        // Return only one element per list:
        $matches = $this->candidateList();
        foreach ($matches as $item) {
          $kids = $this->listPeerElements($item, $restrictToElement);
          if (count($kids) >= $elementInGroup) {
            // Correct for offset: CSS spec says list begins with
            // 1, not 0.
            $found[] = $kids[$elementInGroup -1];
          }
        }
        $this->matches = UniqueElementList::get($found);
        return;
      }
    }
    //print __FUNCTION__ . " size: $groupSize, $elementInGroup.\n";
    
    $matches = $this->candidateList();
    $alreadyChecked = array();
    
    // Handle only the immediate elements
    if ($restrictToElement) {
      foreach($matches as $item) {
        $parent = $item->parentNode;
        $i = 1;
        foreach ($parent->childNodes as $node) {
          if ($node->nodeType == XML_ELEMENT_NODE && $node->tagName == $item->tagName) {
            if ($i % $groupSize == $elementInGroup) {
            //if ($node->nodeIndex - $elementInGroup % $groupSize == 0) {
              print "Found " . $node->getAttribute('id') . PHP_EOL;
              $found[] = $node;
            }
            ++$i; // Only increment for matches.
          }
        }
      }
      $found = UniqueElementList::get($found);
    }
    // Handle any child elements. Since no element selector
    // is effective, then elements of any tag name are considered
    // to be matches.
    else {
      foreach ($matches as $item) {
        $parent = $item->parentNode;
        if (in_array($parent, $alreadyChecked)) {
          // Skip this. It's been done already.
          break;
        }
        $alreadyChecked[] = $parent;

        $i = 1;
        foreach ($parent->childNodes as $node) {
          if ($node->nodeType == XML_ELEMENT_NODE) {
            // Do an + b matching
            if ($i % $groupSize == $elementInGroup) {
              $found[] = $node;
            }
            ++$i;
          }
        }
      }  
    }
    
    
    $this->matches = $found;
    */
  }
  
  protected function nthLastChild($groupSize, $elementInGroup) {
    // If findAnyElement is true, then we 
    // know that there was no element selector
    // preceding this.
    $restrictToElement = !$this->findAnyElement;
        
    $found = array();
    //$groupSize = abs($groupSize);
    if ($elementInGroup < 0) {
      // in an-b, element in group is effectively a-b.
      $elementInGroup = $groupSize + $elementInGroup;
    }
    if ($groupSize == 0) {
      if ($elementInGroup == 0 ) {
        // Both == 0 means no matches:
        $this->matches = array();
        return;
      }
      else {
        // Return only one element per list:
        $matches = $this->candidateList();
        foreach ($matches as $item) {
          $kids = $this->listPeerElements($item, $restrictToElement);
          $count = count($kids);
          if ($count >= $elementInGroup) {
            // Correct for offset: CSS spec says list begins with
            // 1, not 0.
            $found[] = $kids[$count - $elementInGroup];
          }
        }
        $this->matches = UniqueElementList::get($found);
        return;
      }
    }
    //print __FUNCTION__ . " size: $groupSize, $elementInGroup.\n";
    
    $matches = $this->candidateList();
    $alreadyChecked = array();
    
    /*
     * The code below needs some cleaning. The original version
     * only worked after an any-descendant operator, as it did 
     * not adequately constrain element types. It still exists as-is
     * in the else block. However, the optimization (checking parents)
     * has not been tested. If it is not very effective, then we should
     * figure out a more elegant way to handle this.
     */
    
    // Start at the end and go backward.
    $matches = array_reverse($matches);
    
    // Handle only the immediate elements
    if ($restrictToElement) {
      foreach($matches as $item) {
        $parent = $item->parentNode;
        $i = 1;
        for ($j = $parent->childNodes->length - 1; $j > 0; --$j) {
          $node = $parent->childNodes->item($j);
          if ($node->nodeType == XML_ELEMENT_NODE && $node->tagName == $item->tagName) {
            if ($i % $groupSize == $elementInGroup) {
              $found[] = $node;
            }
            ++$i; // Only increment for matches.
          }
        }
      }
      // There are no notes that say whether what order the results
      // shold be retrned in. Should this be reversed again?
      $found = UniqueElementList::get($found);
    }
    // Handle any child elements. Since no element selector
    // is effective, then elements of any tag name are considered
    // to be matches.
    else {
      foreach ($matches as $item) {
        $parent = $item->parentNode;
        if (in_array($parent, $alreadyChecked)) {
          // Skip this. It's been done already.
          break;
        }
        $alreadyChecked[] = $parent;

        $i = 1;
        
        for ($j = $parent->childNodes->length - 1; $j >= 0; ++$j) {
          $node = $parent->childNodes->item($j);
          if ($node->nodeType == XML_ELEMENT_NODE) {
            // Do an + b matching
            if ($i % $groupSize == $elementInGroup) {
              $found[] = $node;
            }
            ++$i;
          }
        }
      }  
    }
    
    
    $this->matches = $found;
    
  }
  
  /**
   * Get a list of peer elements.
   * If $requireSameTag is TRUE, then only peer elements with the same
   * tagname as the given element will be returned.
   *
   * @param $element
   *  A DomElement.
   * @param $requireSameTag
   *  Boolean flag indicating whether all matches should have the same
   *  element name (tagName) as $element.
   * @return
   *  Array of peer elements.
   */
  protected function listPeerElements($element, $requireSameTag = FALSE) {
    $peers = array();
    $parent = $element->parentNode;
    foreach ($parent->childNodes as $node) {
      if ($node->nodeType == XML_ELEMENT_NODE) {
        if ($requireSameTag) {
          // Need to make sure that the tag matches:
          if ($element->tagName == $node->tagName) {
            $peers[] = $node;
          }
        }
        else {
          $peers[] = $node;
        }
      }
    }
    // Return unique element list.
    //return UniqueElementList::get($peers);
    return $peers;
  }
  
  /**
   * Get the nth child (by index) from matching candidates.
   *
   * This is used by pseudo-class handlers.
   */
  protected function childAtIndex($index, $tagName = NULL) {
    $restrictToElement = !$this->findAnyElement;
    $matches = $this->candidateList();
    $defaultTagName = $tagName;
    
    foreach ($matches as $item) {
      $parent = $item->parentNode;
      
      // If a default tag name is supplied, we always use it.
      if (!empty($defaultTagName)) {
        $tagName = $defaultTagName;
      }
      // If we are inside of an element selector, we use the 
      // tag name of the given elements.
      elseif ($restrictToElement) {
        $tagName = $item->tagName;
      }
      // Otherwise, we skip the tag name match.
      else {
        $tagName = NULL;
      }

      // Loop through all children looking for matches.
      $i = 0;
      foreach ($parent->childNodes as $child) {
        if ($child->nodeType !== XML_ELEMENT_NODE) {
          break; // Skip non-elements
        }
        
        // If type is set, then we do type comparison
        if (!empty($tagName)) {
          // Check whether tag name matches the type.
          if ($child->tagName == $tagName) {
            // See if this is the index we are looking for.
            if ($i == $index) {
              $this->matches = array($child);
              return;
            }
            // If it's not the one we are looking for, increment.
            ++$i;
          }
        }
        // We don't care about type. Any tagName will match.
        else {
          if ($i == $index) {
            $this->matches = array($child);
            return;
          }
          ++$i;
        }
      } // End foreach
    }
    
    
  }
  
  protected function nthOfTypeChild($groupSize, $elementInGroup) {
    throw new Exception("Not implemented");
  }
  
  protected function nthLastOfTypeChild($groupSize, $elementInGroup) {
    throw new Exception("Not implemented");    
  }
  
  protected function lang($value) {
    // TODO: This checks for cases where an explicit language is
    // set. The spec seems to indicate that an element should inherit
    // language from the parent... but this is unclear.
    $operator = (strpos($value, '-') !== FALSE) ? self::isExactly : self::containsWithHyphen;
    
    $orig = $this->matches;
    $origDepth = $this->findAnyElement;
    
    // Do first pass
    $this->attribute('lang', $value, $operator);
    $lang = $this->matches; // Temp array for merging.
    
    // Reset
    $this->matches = $orig;
    $this->findAnyElement = $origDepth;
    
    // Do second pass
    $this->attributeNS('lang', 'xml', $value, $operator);
    
    // Merge results
    $this->matches = array_merge($lang, $this->matches);
  }
  
  /**
   * Pseudo-class handler for :not(filter).
   *
   * This does not follow the specification in the following way: The CSS 3
   * selector spec says the value of not() must be a simple selector. This
   * function allows complex selectors.
   *
   * @param string $filter
   *  A CSS selector.
   */
  protected function not($filter) {
    $matches = $this->candidateList();
    $found = array();
    foreach ($matches as $item) {
      $handler = new QueryPathCssEventHandler($item);
      $not_these = $handler->find($filter)->getMatches();
      if (count($not_these) == 0) {
        $found[] = $item;
      }
    }
    // No need to check for unique elements, since the list
    // we began from already had no duplicates.
    $this->matches = $found;    
  }
  
  /**
   * Pseudo-class handler for :has(filter).
   * This can also be used as a general filtering routine.
   */
  public function has($filter) {
    $matches = $this->candidateList();
    $found = array();
    foreach ($matches as $item) {
      $handler = new QueryPathCssEventHandler($item);
      $these = $handler->find($filter)->getMatches();
      if (count($these) > 0) {
        $found[] = $item;
      }      
    }
    $this->matches = UniqueElementList::get($found);
    return $this;
  }
  
  /**
   * Pseudo-class handler for :first-of-type.
   */
  protected function firstOfType() {
    $matches = $this->candidateList();
    $found = array();
    foreach ($matches as $item) {
      $type = $item->tagName;
      $parent = $item->parentNode;
      foreach ($parent->childNodes as $kid) {
        if ($kid->nodeType == XML_ELEMENT_NODE && $kid->tagName == $type) {
          if (!in_array($kid, $found)) {
            $found[] = $kid;
          }
          break;
        }
      }
    }
    $this->matches = $found;
  }
  
  /**
   * Pseudo-class handler for :last-of-type.
   */
  protected function lastOfType() {
    $matches = $this->candidateList();
    $found = array();
    foreach ($matches as $item) {
      $type = $item->tagName;
      $parent = $item->parentNode;
      for ($i = $parent->childNodes->length - 1; $i >= 0; --$i) {
        $kid = $parent->childNodes->item($i);
        if ($kid->nodeType == XML_ELEMENT_NODE && $kid->tagName == $type) {
          if (!in_array($kid, $found)) {
            $found[] = $kid;
          }
          break;
        }
      }
    }
    $this->matches = $found;
  }
  
  /**
   * Pseudo-class handler for :only-child.
   */
  protected function onlyChild() {
    $matches = $this->candidateList();
    $found = array();
    foreach($matches as $item) {
      $parent = $item->parentNode;
      $kids = array();
      foreach($parent->childNodes as $kid) {
        if ($kid->nodeType == XML_ELEMENT_NODE) {
          $kids[] = $kid;
        }
      }
      // There should be only one child element, and
      // it should be the one being tested.
      if (count($kids) == 1 && $kids[0] === $item) {
        $found[] = $kids[0];
      }
    }
    $this->matches = UniqueElementList::get($found);
  }
  
  /**
   * Pseudo-class handler for :empty.
   */
  protected function emptyElement() {
    $found = array();
    $matches = $this->candidateList();
    foreach ($matches as $item) {
      $empty = TRUE;
      foreach($item->childNodes as $kid) {
        // From the spec: Elements and Text nodes are the only ones to
        // affect emptiness.
        if ($kid->nodeType == XML_ELEMENT_NODE || $kid->nodType == XML_TEXT_NODE) {
          $empty = FALSE;
          break;
        }
      }
      if ($empty) {
        $found[] = $item;
      }
    }
    $this->matches = $found;
  }
  
  /**
   * Pseudo-class handler for :only-of-type.
   */
  protected function onlyOfType() {
    $matches = $this->candidateList();
    $found = array();
    foreach ($matches as $item) {
      if (!$item->parentNode) {
        $this->matches = array();
      }
      $parent = $item->parentNode;
      foreach($parent->childNodes as $kid) {
        if ($kid->nodeType == XML_ELEMENT_NODE && $kid->tagName == $item->tagName && $kid !== $item) {
          $this->matches = array();
          break;
        }
      }
      $found[] = $item;
    }
    $this->matches = $found;
  }
  
  /**
   * Check for attr value matches based on an operation.
   */
  protected function attrValMatches($needle, $haystack, $operation) {
    
    if (strlen($haystack) < strlen($needle)) return FALSE;
    
    // According to the spec:  
    // "The case-sensitivity of attribute names in selectors depends on the document language."
    // (6.3.2)
    // To which I say, "huh?". We assume case sensitivity.
    switch ($operation) {
      case CssEventHandler::isExactly:
        return $needle == $haystack;
      case CssEventHandler::containsWithSpace:
        return in_array($needle, explode(' ', $haystack));
      case CssEventHandler::containsWithHyphen:
        return in_array($needle, explode('-', $haystack));
      case CssEventHandler::containsInString:
        return strpos($haystack, $needle) !== FALSE;
      case CssEventHandler::beginsWith:
        return strpos($haystack, $needle) === 0;
      case CssEventHandler::endsWith:
        //return strrpos($haystack, $needle) === strlen($needle) - 1;
        return preg_match('/' . $needle . '$/', $haystack) == 1;
    }
    return FALSE; // Shouldn't be able to get here.
  }
  
  /**
   * As the spec mentions, these must be at the end of a selector or
   * else they will cause errors. Most selectors return elements. Pseudo-elements
   * do not.
   */
  public function pseudoElement($name) {
    // process the pseudoElement
    switch ($name) {
      case 'first-line':
        $matches = $this->candidateList();
        $found = array();
        foreach ($matches as $item) {
          $str = $item->textContent;
          $lines = explode("\n", $str);
          if (!empty($lines)) {
            $line = trim($lines[0]);
            if (!empty($line))
              $found[] = $line;//trim($lines[0]);
          }
        }
        $this->matches = $found;
        break;
      case 'first-letter':
        $matches = $this->candidateList();
        $found = array();
        foreach ($matches as $item) {
          $str = $item->textContent;
          if (!empty($str)) {
            $found[] = substr($str,0, 1);
          }
        }
        $this->matches = $found;
        break;
      case 'before':
      case 'after':
        // There is nothing in a DOM to return for the before and after 
        // selectors.
      case 'selection':
        // With no user agent, we don't have a concept of user selection.
        throw new NotImplementedException("The $name pseudo-element is not implemented.");
        break;
    }
    $this->findAnyElement = FALSE;  
  }
  public function directDescendant() {
    $this->findAnyElement = FALSE;
        
    $kids = array();
    foreach ($this->matches as $item) {
      $kidsNL = $item->childNodes;
      foreach ($kidsNL as $kidNode) {
        if ($kidNode->nodeType == XML_ELEMENT_NODE) {
          $kids[] = $kidNode;
        }
      }
    }
    $this->matches = UniqueElementList::get($kids);
  }
  /**
   * For an element to be adjacent to another, it must be THE NEXT NODE
   * in the node list. So if an element is surrounded by pcdata, there are
   * no adjacent nodes. E.g. in <a/>FOO<b/>, the a and b elements are not 
   * adjacent.
   *
   * In a strict DOM parser, line breaks and empty spaces are nodes. That means
   * nodes like this will not be adjacent: <test/> <test/>. The space between
   * them makes them non-adjacent. If this is not the desired behavior, pass
   * in the appropriate flags to your parser. Example:
   * <code>
   * $doc = new DomDocument();
   * $doc->loadXML('<test/> <test/>', LIBXML_NOBLANKS);
   * </code>
   */
  public function adjacent() {
    $this->findAnyElement = FALSE;
    // List of nodes that are immediately adjacent to the current one.
    $found = array();
    foreach ($this->matches as $item) {
      if (isset($item->nextSibling) && $item->nextSibling->nodeType === XML_ELEMENT_NODE) {
        $found[] = $item->nextSibling;
      }
    }
    $this->matches = UniqueElementList::get($found);
  }
  
  public function anotherSelector() {
    $this->findAnyElement = FALSE;
    // Copy old matches into buffer.
    if (!empty($this->matches)) {
      //$this->alreadyMatched[] = $this->matches;
      $this->alreadyMatched = array_merge($this->alreadyMatched, $this->matches);
    }
    
    // Start over at the top of the tree.
    $this->findAnyElement = TRUE; // Reset depth flag.
    $this->matches = array($this->dom);
  }
  
  /**
   * Get all nodes that are siblings to currently selected nodes.
   *
   * If two passed in items are siblings of each other, neither will
   * be included in the list of siblings. Their status as being candidates
   * excludes them from being considered siblings.
   */
  public function sibling() {
    $this->findAnyElement = FALSE;
    // Get the nodes at the same level.
    
    if (!empty($this->matches)) {
      $sibs = array();
      foreach ($this->matches as $item) {
        $candidates = $item->parentNode->childNodes;
        foreach ($candidates as $candidate) {
          if ($candidate->nodeType === XML_ELEMENT_NODE && $candidate !== $item) {
            $sibs[] = $candidate;
          }
        }
      }
      // Do we need to remove duplicates for any reason?
      $this->matches = UniqueElementList::get($sibs);
    }
  }
  
  /**
   * Get any descendant.
   */
  public function anyDescendant() {
    // Get children:
    $found = array();
    foreach ($this->matches as $item) {
      $kids = $item->getElementsByTagName('*');
      $found = array_merge($found, $this->nodeListToArray($kids));
    }
    $this->matches = UniqueElementList::get($found);
    
    // Set depth flag:
    $this->findAnyElement = TRUE;
  }
  
  /**
   * Determine what candidates are in the current scope.
   *
   * This is a utility method that gets the list of elements
   * that should be evaluated in the context. If $this->findAnyElement
   * is TRUE, this will return a list of every element that appears in
   * the subtree of $this->matches. Otherwise, it will just return 
   * $this->matches.
   */
  private function candidateList() {
    if ($this->findAnyElement) {
      return $this->getAllCandidates($this->matches);
    }
    return $this->matches;
  }
  
  /**
   * Get a list of all of the candidate elements.
   *
   * This is used when $this->findAnyElement is TRUE.
   * @param $elements
   *  A list of current elements (usually $this->matches).
   *
   * @return 
   *  A list of all candidate elements.
   */
  private function getAllCandidates($elements) {
    $found = array();
    foreach ($elements as $item) {
      $found[] = $item; // put self in
      $nl = $item->getElementsByTagName('*');
      foreach ($nl as $node) $found[] = $node;
    }
    return UniqueElementList::get($found);
  }
  
  public function nodeListToArray($nodeList) {
    $array = array();
    foreach ($nodeList as $node) {
      if ($node->nodeType == XML_ELEMENT_NODE) {
        $array[] = $node;
      }
    }
    return $array;
  }
  
}

/**
 * Specialized handler for only parsing the contents of a negation
 * selector.
 *
 * According to the CSS 3 selector specification, the negation pseudo-class
 * (:not()) can only contain a simple selector with no negation handler and 
 * no pseudo-elements. To meet this requirement, this class implements a 
 * restricted version of the CssEventHandler that will only parse simple 
 * selectors.
 *
 * @deprecated This has been replaced by a full CssEventHandler implementation.
 */
class NegationCssEventHandler extends QueryPathCssEventHandler {
  public function find($filter) {
    $parser = new CssParser($filter, $this);
    $parser->parseSimpleSelector();
    return $this;
  }
}

/**
 * Utility class to winnow a list of Elements down to unique ones.
 * This uses strong equality.
 *
 * Why not use array_unique()? Because that requires a cast to a string,
 * which doesn't work well for elements (loses idempotence).
 */
class UniqueElementList {
  var $result;
  
  /**
   * Given an array of elements, return an array of unique elements.
   * Static utility method.
   *
   * @param array $list
   *  An array of objects.
   * @return 
   *  An array of objects with all duplicates removed.
   */
  public static function get($list) {
    $uel = new UniqueElementList($list);
    return $uel->toArray();
  }
  
  /**
   * Construct a new list and filter it.
   */
  public function __construct($list) {
    $this->result = array();
    foreach ($list as $item) {
      $this->compare($item);
    }
  }
  
  /**
   * Get the list as an array.
   */
  public function toArray() {
    return $this->result;
  }
  
  /**
   * Compare the current element to previous elements in the list.
   * If it is unique, it is kept. If it is not, it is discarded from
   * the list.
   */
  protected function compare($element) {
    if (!in_array($element, $this->result, TRUE)) {
      $this->result[] = $element;
    }
  }
}

class NamespaceMap {
  protected $map = array();
  
  public function __construct($dom) {
    $all = $dom->getElementsByName('*');
    foreach ($all as $e) {
      $attrs = $e->getAttributeNS('xmlns');
    }
  }
  
  public function getNSURI($name) {
    if (array_key_exists($this->map, $name)) {
      return $this->map[$name];
    }
  }
}

/**
 * Exception thrown for unimplemented CSS.
 *
 * This is thrown in cases where some feature is expected, but the current 
 * implementation does not support that feature.
 */
class NotImplementedException extends Exception {}