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
      
      if ($document instanceof DOMDocument) {
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
    $this->matches = $query->getMatches();
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
    // Always return first match's attr.
    return $this->matches[0]->getAttribute($name);
  }  
  
  public function eq($index) {
    $this->matches = array($this->matches[$index]);
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
    $this->matches = $found;
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
    $this->matches = $found;
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
    $this->matches = $found;
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
    $this->matches = $found;
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
    $this->matches = $found;
    return $this;
  }
  
  public function slice($start, $end = NULL) {
    if ($start >= $this->size()) {
      $this->matches = array();
      return $this;
    }
    $this->matches = array_slice($this->matches, $start, $end);
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
  
  public function wrap($markup) {
    $data = $this->prepareInsert($markup);
    
    // Find the deepest element.
    
    // Remove the match
    
    // If there is more than one match, clone the wrapper.
    
    // Add the match to the wrapper
    
    // Insert the wrapper.
  }
  
  public function wrapAll($markup) {
    
  }
  
  public function wrapInner($markup) {
    
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
    $this->matches = $winner;//array($winner);
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
    } elseif ($depth > $deepest) {
      $current = array($ele);
      $deepest = $depth;
    } elseif ($depth === $deepest) {
      $current[] = $ele;
    }
    return $current;
  }
  
  /**
   * - If item is a string, this is converted into a document fragment and returned.
   * - If item is a QueryPath, then the first item is retrieved and this call function
   *   is called recursivel.
   * - If the item is a DOMNode, it is imported into the current DOM if necessary.
   * - If the item is a SimpleXMLElement, it is converted into a DOM node and then
   *   imported.
   */
  protected function prepareInsert($item) {
    if (is_string($item)) {
      if ($this->isXMLish($item)) {
        $frag = $this->document->createDocumentFragment();
        $frag->appendXML($item);
        return $frag;
      }
      else {
        return $this->document->createElement($item);
      }
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
  
  public function html($markup = NULL) {
    if (isset($markup)) {
      // Parse the HTML and insert it into the DOM
      return $this;
    }
    $length = $this->size();
    if ($length == 0) {
      return NULL;
    }
    // Only retrn the first item -- that's what JQ does.
    
  }
  public function text($text = NULL) {
    if (isset($text)) {
      foreach($this->matches as $m) $m->textContent = $text;
      return $this;
    }
    // Returns all text as one string:
    $buf = '';
    foreach ($this->matches as $m) $buf .= $m->textContent;
    return $buf;
  }
  public function val() {}
  
  public function xml($markup = NULL) { return $this->html($markup); }
  
  public function end() { return $this; }
  public function andSelf() {}
  
  public function add() {}
  public function children() {}
  public function siblings() {}
  public function contents() {}
  public function next() {}
  public function nextAll() {}
  public function parent() {}
  public function parents() {}
  public function prev() {}
  public function prevAll() {}
  
  
  public function appendTo($something) {}
  public function prependTo($something) {}
  public function insertAfter($something) {}
  public function after($something) {}
  public function insertBefore($something) {}
  public function before($something) {}
  
  public function clear() {}
  public function removeAll($selector) {}
  public function replaceWith($something) {}
  public function replaceAll($selector) {}
  

  
  public function remoteAttr($name) {}
  public function addClass($class) {}
  public function removeClass($class) {}
  public function hasClass($class) {}
  
  public function cloneE() {}
  public function serialize() {}
  public function serializeArray() {}
  
  /////// PRIVATE FUNCTIONS ////////
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
