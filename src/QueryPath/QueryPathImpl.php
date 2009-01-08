<?php
/**
 * This file contains the QueryPathImpl, the main implementation of the 
 * QueryPath interface.
 * @see QueryPath
 */

/**
 * This is the main implementation of the QueryPath interface.
 *
 * It provides core services for the Query Path. The class is final.
 * To extend the QueryPath library, you should write a decorator that
 * extends QueryPathExtension.
 */
final class QueryPathImpl implements QueryPath {
  private $document = NULL;
  private $options = array();
  private $matches = array();
  private $last = array(); // Last set of matches.
  
  /**
   * Take a list of DOMNodes and return a unique list.
   *
   * Constructs a new array of elements with no duplicate DOMNodes.
   */
  public static function unique($list) {
    return UniqueElementList::get($list);
  }
  
  /**
   * Create a new query path object.
   * @param mixed $document
   *  A path, XML/HTML string, DOMNode, DOMDocument, or SimpleXMLElement.
   */
  public function __construct($document, $string = NULL, $options = array()) {
    $string = trim($string);
    $this->options = $options;

    // Figure out if document is DOM, HTML/XML, or a filename
    if (is_object($document)) {
      
      if ($document instanceof QueryPath) {
        $this->matches = $document->get();
        if (!empty($this->matches))
          $this->document = $this->matches[0]->ownerDocument;
      }
      elseif ($document instanceof DOMDocument) {
        $this->document = $document;
        $this->matches = array($document->documentElement);
      }
      elseif ($document instanceof DOMNode) {
        $this->document = $document->ownerDocument;
        $this->matches = array($document);
      }
      elseif ($document instanceof SimpleXMLElement) {
        $import = dom_import_simplexml($document);
        $this->document = $import->ownerDocument;
        $this->matches = array($import);
      }
      else {
        throw new QueryPathException('Unsupported class type: ' . get_class($document));
      }
    }
    elseif (is_array($document)) {
      if (!empty($document) && $document[0] instanceof DOMNode) {
        $this->matches = $document;
        $this->document = $this->matches[0]->ownerDocument;
      }
    }
    elseif ($this->isXMLish($document)) {
      // $document is a string with XML
      $this->document = $this->parseXMLString($document);
      $this->matches = array($this->document->documentElement);
    }
    else {
      // $document is a filename
      $this->document = $this->parseXMLFile($document);
      $this->matches = array($this->document->documentElement);
    }
    
    if (isset($string) && strlen($string) > 0) {
      /*
      $query = new QueryPathCssEventHandler($this->document);
      print "Finding $string \n";
      $query->find($string);
      $this->matches = $query->getMatches();
      */
      $this->find($string);
      //print_r($this->matches);
    }
    
  }
  
  public function find($selector) {
    $query = new QueryPathCssEventHandler($this->matches);
    $query->find($selector);
    //$this->matches = $query->getMatches();
    $this->setMatches($query->getMatches());
    return $this;
  }
  
  public function size() {
    return count($this->matches);
  }
  
  public function get($index = NULL) {
    if (isset($index)) {
      return ($this->size() > $index) ? $this->matches[$index] : NULL;
    }
    return $this->matches;
  }
  public function attr($name, $value = NULL) {
    // multi-setter
    if (is_array($name)) {
      foreach ($name as $k => $v) {
        foreach ($this->matches as $m) $m->setAttribute($k, $v);
      }
      return $this;
    }
    // setter
    if (isset($value)) {
      foreach ($this->matches as $m) $m->setAttribute($name, $value);
      return $this;
    }
    
    //getter
    if (empty($this->matches)) return NULL;
    
    // Special node type handler:
    if ($name == 'nodeType') {
      return $this->matches[0]->nodeType;
    }
    
    // Always return first match's attr.
    return $this->matches[0]->getAttribute($name);
  }
  
  public function removeAttr($name) {
    foreach ($this->matches as $m) {
      //if ($m->hasAttribute($name))
        $m->removeAttribute($name);
    }
    return $this;
  }
  
  public function eq($index) {
    $this->setMatches(array($this->matches[$index]));
    return $this;
  }
  
  public function is($selector) {
    foreach ($this->matches as $m) {
      $q = new QueryPathCssEventHandler($m);
      if (count($q->find($selector)->getMatches())) {
        return TRUE;
      }
    }
    return FALSE;
  }
  
  public function filter($selector) {
    $found = array();
    foreach ($this->matches as $m) if (qp($m)->is($selector)) $found[] = $m;
    $this->setMatches($found);
    return $this;
  }
  
  public function filterLambda($fn) {
    $function = create_function('$index, $item', $fn);
    $found = array();
    $count = count($this->matches);
    for ($i = 0; $i < $count; ++$i) {
      $item = $this->matches[$i];
      if ($function($i, $item) !== FALSE) $found[] = $item;
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function filterCallback($callback) {
    $found = array();
    if (is_array($callback)) {
      if (is_object($callback[0])) {
        // Object/func
        $obj = $callback[0];
        $func = $callback[1];
        for ($i = 0; $i < $this->size(); ++$i) {
          $item = $this->matches[$i];
          if ($obj->$func($i, $item) !== FALSE) $found[] = $item;
        }
      }
      else {
        // Class/func
        $class = $callback[0];
        $func = $callback[1];
        for ($i = 0; $i < $this->size(); ++$i) {
          $item = $this->matches[$i];
          // FIXME: This might only work on >= 5.2. Plus it's lame.
          if (${"$class::$func"}($i, $item) !== FALSE) $found[] = $item;
        }
      }
    }
    else {
      // function
      for ($i = 0; $i < $this->size(); ++$i) {
        $item = $this->matches[$i];
        if ($callback($i, $item) !== FALSE) $found[] = $item;
      }
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function not($selector) {
    $found = array();
    if ($selector instanceof DOMElement) {
      foreach ($this->matches as $m) if ($m !== $selector) $found[] = $m; 
    }
    elseif (is_array($selector)) {
      foreach ($this->matches as $m) if (!in_array($m, $selector)) $found[] = $m; 
    }
    else {
      foreach ($this->matches as $m) if (!qp($m)->is($selector)) $found[] = $m;
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function index($subject) {
    for ($i = 0; $i < $this->size(); ++$i) {
      if ($this->matches[$i] === $subject) {
        return $i;
      }
    }
    return FALSE;
  }
  
  public function map($callback) {
    $found = array();
    if (is_array($callback)) {
      if (is_object($callback[0])) {
        // Object/func
        $obj = $callback[0];
        $func = $callback[1];
        for ($i = 0; $i < $this->size(); ++$i) {
          $item = $this->matches[$i];
          $c = $obj->$func($i, $item);
          if (isset($c)) {
            is_array($c) ? $found = array_merge($found, $c) : $found[] = $c;
          }
        }
      }
      else {
        // Class/func
        $class = $callback[0];
        $func = $callback[1];
        for ($i = 0; $i < $this->size(); ++$i) {
          $item = $this->matches[$i];
          // FIXME: This might only work on >= 5.2. Plus it's lame.
          $c = ${"$class::$func"}($i, $item);
          if (isset($c)) {
            is_array($c) ? $found = array_merge($found, $c) : $found[] = $c;
          }
        }
      }
    }
    else {
      // function
      for ($i = 0; $i < $this->size(); ++$i) {
        $item = $this->matches[$i];
        $c = $callback($i, $item); 
        if (isset($c)) {
          is_array($c) ? $found = array_merge($found, $c) : $found[] = $c;
        }
      }
    }
    $this->setMatches($found, FALSE);
    return $this;
  }
  
  public function slice($start, $end = NULL) {
    if ($start >= $this->size()) {
      $this->setMatches(array());
      return $this;
    }
    $this->setMatches(array_slice($this->matches, $start, $end));
    return $this;
  }
  
  public function each($callback) {
    if (is_array($callback)) {
      if (is_object($callback[0])) {
        // Object/func
        $obj = $callback[0];
        $func = $callback[1];
        for ($i = 0; $i < $this->size(); ++$i) {
          $item = $this->matches[$i];
          if ($obj->$func($i, $item) === FALSE) return $this;
        }
      }
      else {
        // Class/func
        $class = $callback[0];
        $func = $callback[1];
        for ($i = 0; $i < $this->size(); ++$i) {
          $item = $this->matches[$i];
          // FIXME: This might only work on >= 5.2. Plus it's lame.
          if (${"$class::$func"}($i, $item) === FALSE) return $this;
        }
      }
    }
    else {
      // function
      for ($i = 0; $i < $this->size(); ++$i) {
        $item = $this->matches[$i];
        if ($callback($i, $item) === FALSE) return $this; 
      }
    }
    return $this;
  }

  public function eachLambda($lambda) {
    for ($index = 0; $index < $this->size(); ++$index) {
      $fn = create_function('$index, &$item', $lambda);
      $item = $this->matches[$index];  
      if ($fn($index, $item) === FALSE) return $this;
    }
    return $this;
  }
  
  public function append($data) {
    $data = $this->prepareInsert($data);
    if (isset($data)) {
      foreach ($this->matches as $m) $m->appendChild($data);
    }
    return $this;
  }
  
  public function appendTo(QueryPath $dest) {
    foreach ($this->matches as $m) $dest->append($m);
    return $this;
  }
  
  public function prepend($data) {
    $data = $this->prepareInsert($data);
    if (isset($data)) {
      foreach ($this->matches as $m) {
        if ($m->hasChildNodes())
          $m->insertBefore($data, $m->childNodes->item(0));
        else
          $m->appendChild($data);
      }
    }
    return $this;
  }
  
  public function prependTo(QueryPath $dest) {
    foreach ($this->matches as $m) $dest->prepend($m);
    return $this;
  }

  
  public function before($data) {
    $data = $this->prepareInsert($data);
    foreach ($this->matches as $m) $m->parentNode->insertBefore($data, $m);
    
    return $this;
  }
  public function insertBefore(QueryPath $dest) {
    foreach ($this->matches as $m) $dest->before($m);
    return $this;
  }
  
  public function insertAfter(QueryPath $dest) {
    foreach ($this->matches as $m) $dest->after($m);
    return $this;
  }
  
  public function after($data) {
    $data = $this->prepareInsert($data);
    foreach ($this->matches as $m) {
      if (isset($m->nextSibling)) 
        $m->parentNode->insertBefore($data, $m->nextSibling);
      else
        $m->parentNode->appendChild($data);
    }
    return $this;
  }
  
  public function replaceWith($new) {
    $data = $this->prepareInsert($new);
    $found = array();
    foreach ($this->matches as $m) {
      $parent = $m->parentNode;
      $parent->insertBefore($data->cloneNode(TRUE), $m);
      $found[] = $parent->removeChild($m);
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function wrap($markup) {
    $data = $this->prepareInsert($markup);
    
    foreach ($this->matches as $m) {
      $copy = $data->firstChild->cloneNode(TRUE);
      
      // XXX: Should be able to avoid doing this over and over.
      if ($copy->hasChildNodes()) {
        $deepest = $this->deepestNode($copy); 
        $bottom = $deepest[0];
      }
      else
        $bottom = $copy;

      $parent = $m->parentNode;
      $parent->insertBefore($copy, $m);
      $m = $parent->removeChild($m);
      $bottom->appendChild($m);
      //$parent->appendChild($copy);
    }
    return $this;  
  }
  
  public function wrapAll($markup) {
    if (empty($this->matches))
      return;
    
    $data = $this->prepareInsert($markup);
    if ($data->hasChildNodes()) {
      $deepest = $this->deepestNode($data); 
      $bottom = $deepest[0];
    }
    else
      $bottom = $data;

    $parent = $this->matches[0]->parentNode;
    $parent->insertBefore($data, $this->matches[0]);
    foreach ($this->matches as $m) {
      $bottom->appendChild($m->parentNode->removeChild($m));
    }
    return $this;
  }
  
  public function wrapInner($markup) {
    $data = $this->prepareInsert($markup);
    if ($data->hasChildNodes()) {
      $deepest = $this->deepestNode($data); 
      $bottom = $deepest[0];
    }
    else
      $bottom = $data;
      
    foreach ($this->matches as $m) {
      if ($m->hasChildNodes()) {
        while($m->firstChild) {
          $kid = $m->removeChild($m->firstChild);
          $bottom->appendChild($kid);
        }
      }
      $m->appendChild($data);
    }
    return $this; 
  }
  
  public function deepest() {
    $deepest = 0;
    $winner = array();
    foreach ($this->matches as $m) {
      $local_deepest = 0;
      $local_ele = $this->deepestNode($m, 0, NULL, $local_deepest);
      if ($local_deepest > $deepest) {
        $winner = $local_ele;
        $deepest = $local_deepest;
      }
      elseif ($local_deepest == $deepest) {
        $winner = array_merge($winner, $local_ele);
      }
    }
    $this->setMatches($winner);//array($winner);
    return $this;
  }
  
  /**
   * A depth-checking function. Typically, it only needs to be
   * invoked with the first parameter. The rest are used for recursion.
   * @see deepest();
   */
  protected function deepestNode(DOMNode $ele, $depth = 0, $current = NULL, &$deepest = NULL) {
    if (!isset($current)) $current = array($ele);
    if (!isset($deepest)) $deepest = $depth;
    if ($ele->hasChildNodes()) {
      foreach ($ele->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE) {
          $current = $this->deepestNode($child, $depth + 1, $current, $deepest);
        }
      }
    }
    elseif ($depth > $deepest) {
      $current = array($ele);
      $deepest = $depth;
    }
    elseif ($depth === $deepest) {
      $current[] = $ele;
    }
    return $current;
  }
  
  /**
   * Prepare an item for insertion into a DOM.
   *
   * This handles a variety of boilerplate tasks that need doing before an 
   * indeterminate object can be inserted into a DOM tree.
   * - If item is a string, this is converted into a document fragment and returned.
   * - If item is a QueryPath, then the first item is retrieved and this call function
   *   is called recursivel.
   * - If the item is a DOMNode, it is imported into the current DOM if necessary.
   * - If the item is a SimpleXMLElement, it is converted into a DOM node and then
   *   imported.
   */
  protected function prepareInsert($item) {
    if (is_string($item)) {
      /* This isn't what jQuery does, so we won't do it that way.
      if ($this->isXMLish($item)) {
        $frag = $this->document->createDocumentFragment();
        $frag->appendXML($item);
        return $frag;
      }
      else {
        return $this->document->createElement($item);
      }
      */
      $frag = $this->document->createDocumentFragment();
      $frag->appendXML($item);
      return $frag;
    }
    elseif ($item instanceof QueryPath && $item->size()  > 0) {
      return $this->prepareInsert($item->get(0));
    }
    elseif ($item instanceof DOMNode) {
      if ($item->ownerDocument !== $this->document) {
        // Deep clone this and attach it to this document
        $item = $this->document->importNode($item, TRUE);
      }
      return $item;
    }
    elseif ($item instanceof SimpleXMLElement) {
      $element = dom_import_simplexml($item);
      return $this->document->importNode($element, TRUE);
    }
    // What should we do here?
    throw new QueryPathException("Cannot prepare item of unsupported type.");
  }
  
  public function tag() {
    return ($this->size() > 0) ? $this->matches[0]->tagName : '';
  }
  
  public function remove($selector = NULL) {
    
    if(!empty($selector))
      $this->find($selector);
    
    $found = array();
    foreach ($this->matches as $item) {
      // The item returned is (according to docs) different from 
      // the one passed in, so we have to re-store it.
      $found[] = $item->parentNode->removeChild($item);
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function replaceAll($selector, DOMDocument $document) {
    $replacement = $this->size() > 0 ? $this->matches[0] : $this->document->createTextNode('');
    
    $c = new QueryPathCssEventHandler($document);
    $c->find($selector);
    $temp = $c->getMatches();
    foreach ($temp as $item)
      $item->parentNode->replaceChild($item, $replacement);
      
    return $this;
  }
  
  public function add($selector) {
    $found = qp($this->document, $selector)->get();
    // XXX: Need to test if this correctly handles duplicates.
    $this->setMatches(array_merge($this->matches, $found));
    return $this;
  }
  
  public function end() {
    // Note that this does not use setMatches because it must set the previous
    // set of matches to empty array.
    $this->matches = $this->last;
    $this->last = array();
    return $this;
  }
  public function andSelf() {
    $this->setMatches(array_merge($this->matches, $this->last));
    return $this;
  }
  
  public function removeChildren() {
    foreach ($this->matches as $m) {
      while($kid = $m->firstChild) {
        $m->removeChild($kid);
      }
    }
    return $this;
  }
  
  public function children($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      foreach($m->childNodes as $c) {
        if ($c->nodeType == XML_ELEMENT_NODE) $found[] = $c;
      }
    }
    if (empty($selector)) {
      $this->setMatches($found);
    }
    else {
      $this->matches = $found; // Don't buffer this. It is temporary.
      $this->filter($selector);
    }
    return $this;
  }
  
  public function contents() {
    $found = array();
    foreach ($this->matches as $m) {
      foreach ($m->childNodes as $c) {
        $found[] = $c;
      }
    }
    $this->setMatches(UniqueElementList::get($found));
    return $this;
  }
  
  public function siblings($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      $parent = $m->parentNode;
      foreach ($parent->childNodes as $n) {
        if ($n->nodeType == XML_ELEMENT_NODE && $n !== $m) {
          $found[] = $n;
        }
      }
    }
    if (empty($selector)) {
      $this->setMatches($found);
    }
    else {
      $this->matches = $found; // Don't buffer this. It is temporary.
      $this->filter($selector);
    }
    return $this;
  }
  
  public function parent($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      while ($m->parentNode->nodeType !== XML_DOCUMENT_NODE) {
        $m = $m->parentNode;
        // Is there any case where parent node is not an element?
        if ($m->nodeType === XML_ELEMENT_NODE) {
          if (!empty($selector)) {
            if (qp($m)->is($selector) > 0) {
              $found[] = $m;
              break;
            }
          }
          else {
            $found[] = $m;
            break;
          }
        }
      }
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function parents($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      while ($m->parentNode->nodeType !== XML_DOCUMENT_NODE) {
        $m = $m->parentNode;
        // Is there any case where parent node is not an element?
        if ($m->nodeType === XML_ELEMENT_NODE) {
          if (!empty($selector)) {
            if (qp($m)->is($selector) > 0)
              $found[] = $m;
          }
          else 
            $found[] = $m;
        }
      }
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function html($markup = NULL) {
    if (isset($markup)) {
      // Parse the HTML and insert it into the DOM
      //$doc = DOMDocument::loadHTML($markup);
      $doc = $this->document->createDocumentFragment();
      $doc->appendXML($markup);
      $this->removeChildren();
      $this->append($doc);
      return $this;
    }
    $length = $this->size();
    if ($length == 0) {
      return NULL;
    }
    // Only return the first item -- that's what JQ does.
    $first = $this->matches[0];
    if ($first instanceof DOMDocument || $first->isSameNode($first->ownerDocument->documentElement)) {
      return $this->document->saveHTML();
    }
    // saveHTML cannot take a node and serialize it.
    return $this->document->saveXML($this->matches[0]);
    
  }
  public function text($text = NULL) {
    if (isset($text)) {
      $this->removeChildren();
      $textNode = $this->document->createTextNode($text);
      foreach($this->matches as $m) $m->appendChild($textNode);
      return $this;
    }
    // Returns all text as one string:
    $buf = '';
    foreach ($this->matches as $m) $buf .= $m->textContent;
    return $buf;
  }
  
  public function val($value = NULL) {
    if (isset($value)) {
      foreach ($this->matches as $m) $m->attr('value', $value);
      return;
    }
    return empty($this->matches) ? NULL : $this->matches[0]->attr('value');
  }
  
  public function xml($markup = NULL) {
    if (isset($markup)) {
      $doc = $this->document->createDocumentFragment();
      $doc->appendXML($markup);
      $this->removeChildren();
      $this->append($doc);
      return $this;
    }
    $length = $this->size();
    if ($length == 0) {
      return NULL;
    }
    // Only return the first item -- that's what JQ does.
    $first = $this->matches[0];
    if ($first instanceof DOMDocument || $first->isSameNode($first->ownerDocument->documentElement)) {
      return $this->document->saveXML();
    }
    // saveHTML cannot take a node and serialize it.
    return $this->document->saveXML($this->matches[0]);
  }
  
  public function writeXML() {
    print $this->document->saveXML();
    return $this;
  }
  
  public function writeHTML($headers = array()) {
    print $this->document->saveHTML();
    return $this;
  }

  public function next($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      while (isset($m->nextSibling)) {
        $m = $m->nextSibling;
        if ($m->nodeType === XML_ELEMENT_NODE) {
          if (!empty($selector)) {
            if (qp($m)->is($selector) > 0) {
              $found[] = $m;
              break;
            }
          }
          else {
            $found[] = $m;
            break;
          }
        }
      }
    }
    $this->setMatches($found);
    return $this;
  }
  public function nextAll($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      while (isset($m->nextSibling)) {
        $m = $m->nextSibling;
        if ($m->nodeType === XML_ELEMENT_NODE) {
          if (!empty($selector)) {
            if (qp($m)->is($selector) > 0) {
              $found[] = $m;
            }
          }
          else {
            $found[] = $m;
          }
        }
      }
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function prev($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      while (isset($m->previousSibling)) {
        $m = $m->previousSibling;
        if ($m->nodeType === XML_ELEMENT_NODE) {
          if (!empty($selector)) {
            if (qp($m)->is($selector)) {
              $found[] = $m;
              break;
            }
          }
          else {
            $found[] = $m;
            break;
          }
        }
      }
    }
    $this->setMatches($found);
    return $this;
  }
  public function prevAll($selector = NULL) {
    $found = array();
    foreach ($this->matches as $m) {
      while (isset($m->previousSibling)) {
        $m = $m->previousSibling;
        if ($m->nodeType === XML_ELEMENT_NODE) {
          if (!empty($selector)) {
            if (qp($m)->is($selector)) {
              $found[] = $m;
            }
          }
          else {
            $found[] = $m;
          }
        }
      }
    }
    $this->setMatches($found);
    return $this;
  }
  
  public function addClass($class) {
    foreach ($this->matches as $m) {
      if ($m->hasAttribute('class')) {
        $val = $m->getAttribute('class');
        $m->setAttribute('class', $val . ' ' . $class);
      }
      else {
        $m->setAttribute('class', $class);
      }
    }
    return $this;
  }
  public function removeClass($class) {
    foreach ($this->matches as $m) {
      if ($m->hasAttribute('class')) {
        $vals = explode(' ', $m->getAttribute('class'));
        if (in_array($class, $vals)) {
          $buf = array();
          foreach ($vals as $v) {
            if ($v != $class) $buf[] = $v;
          }
          if (count($buf) == 0)
            $m->removeAttribute('class');
          else
            $m->setAttribute('class', implode(' ', $buf));
        }
      }
    }
    return $this;
  }
  public function hasClass($class) {
    foreach ($this->matches as $m) {
      if ($m->hasAttribute('class')) {
        $vals = explode(' ', $m->getAttribute('class'));
        if (in_array($class, $vals)) return TRUE;
      }
    }
    return FALSE;
  }
  
  public function cloneAll() {
    $found = array();
    foreach ($this->matches as $m) $found[] = $m->cloneNode(TRUE);
    $this->setMatches($found, FALSE);
    return $this;
  }
  
  /**
   * Clone the QueryPath.
   *
   * This makes a deep clone of the elements inside of the QueryPath. It also
   * destroys the history buffer, so an end() will not return you to a 
   * pre-cloned state.
   *
   * This clones only the QueryPathImpl, not all of the decorators. The
   * clone operator in PHP should handle the cloning of the decorators.
   */
  public function __clone() {
    //$found = array();
    // We don't use cloneAll because that would destroy the present
    // context.
    //foreach ($this->matches as $m) $found[] = $m->cloneNode(TRUE);
    //return new QueryPathImpl($found);
    $this->cloneAll();
  }
  
  /////// PRIVATE FUNCTIONS ////////
  // Functions are declared private because nothing can subclass QueryPathImpl.
  // (It is, after all, final). Instead of extending this class, you 
  // should create a decorator for the class.
  
  // Subclasses may not implment this. Altering them may be altering
  // core assumptions about how things work. Instead, classes should 
  // override the constructor and pass in only one of the parsed types
  // that this class expects.
  private function isXMLish($string) {
    return preg_match(ML_EXP, $string) > 0;
  }
  
  private function parseXMLString($string) {
    $document = new DOMDocument();
    $lead = strtolower(substr($string, 0, 5)); // <?xml
    if ($lead == '<?xml') {
      $document->loadXML($string);
    }
    else {
      $document->loadHTML($string);
    }
    return $document;
  }
  
  /**
   * A utility function for setting the current set of matches.
   * It makes sure the last matches buffer is set (for end() and andSelf()).
   */
  private function setMatches($matches, $unique = TRUE) {
    // This causes a lot of overhead....
    if ($unique) $matches = self::unique($matches);
    
    $this->last = $this->matches;
    $this->matches = $matches;
  }
  
  /**
   * Parse just a fragment of XML.
   * This will automatically prepend an <?xml ?> declaration before parsing.
   * @param string $string 
   *   Fragment to parse.
   * @return DOMDocumentFragment 
   *   The parsed document fragment.
   */
  private function parseXMLFragment($string) {
    $frag = $this->document->createDocumentFragment();
    $frag->appendXML($string);
    return $frag;
  }
  
  private function parseXMLFile($filename) {
    $document = new DOMDocument();
    $lastDot = strrpos($filename, '.');
    if ($lastDot !== FALSE && strtolower(substr($filename, $lastDot)) == '.html') {
      // Try parsing it as HTML.
      $document->loadHTMLFile($filename);
    }
    else {
      $document->load($filename);
    }
    return $document;
  }

}
