<?php
/**
 * This file contains the Query Path extension tools.
 *
 * Query Path can be extended to support additional features. To do this, 
 * you need only create a new class that extends QueryPathExtender and add your own
 * methods. This class can then be registered as an extension. It will then be
 * available through Query Path.
 * @package QueryPath
 */
 
/**
 * The extension class for Query Path.
 * This class is used to build extensions to the Query Path library.
 * To create a new custom Query Path extension, create a new class that
 * extends this, add your own methods, and then register it. The extension
 * will then be automatically available.
 */
class QueryPathExtender implements QueryPath {
  // We don't use the magic __call() becase of overhead.
  
  public static $extensionRegistry = array();
  public static function extend($classname) {
    self::$extensionRegistry[] = $classname;
  }
  
  private $dqi = NULL;
  public function __construct($string, $document = NULL, $options = array()) {
    $this->dqi = new QueryPathImpl($string, $document, $options);
    // Decorate the original.
    foreach (self::$extensionRegistry as $ext) {
      $this->dqi = new $ext($this->dqi);
    }
  }
  
  public function find($selector) {return $this->dqi->find($selector);}
  public function size() {return $this->dqi->size();}
  public function get($index = NULL) {return $this->dqi->get($index);}
  public function eq($index) {return $this->dqi->eq($index);}
  public function filter($selector) {return $this->dqi->filter($selector);}
  public function is() {return $this->dqi->is();}
  public function map() {return $this->dqi->map();}
  public function not($selector) {return $this->dqi->not($selector);}
  public function slice() {return $this->dqi->slice();}
  public function each() {return $this->dqi->each();}
  public function index($subject) {return $this->dqi->index($subject);}
  public function html($markup = NULL) {return $this->dqi->html($markup);}
  public function text($text) {return $this->dqi->text($text);}
  public function val() {return $this->dqi->val();}
  public function xml($markup = NULL) {return $this->dqi->xml($markup);}
  public function end() {return $this->dqi->end();}
  public function andSelf() {return $this->dqi->andSelf();}
  public function add() {return $this->dqi->add();}
  public function children() {return $this->dqi->children($selector);}
  public function siblings() {return $this->dqi->siblings($selector);}
  public function contents() {return $this->dqi->contents($selector);}
  public function next() {return $this->dqi->next();}
  public function nextAll() {return $this->dqi->nextAll();}
  public function parent() {return $this->dqi->parent($selector);}
  public function parents() {return $this->dqi->parents($selector);}
  public function prev() {return $this->dqi->prev($selector);}
  public function prevAll() {return $this->dqi->prevAll($selector);}
  public function append($apendage) {return $this->dqi->append($selector);}
  public function prepend($prependage) {return $this->dqi->prepend($selector);}
  public function appendTo($something) {return $this->dqi->appendTo($selector);}
  public function prependTo($something) {return $this->dqi->prependTo($selector);}
  public function insertAfter($something) {return $this->dqi->insertAfter($selector);}
  public function after($something) {return $this->dqi->after($selector);}
  public function insertBefore($something) {return $this->dqi->insertBefore($selector);}
  public function before($something) {return $this->dqi->before($selector);}
  public function wrap($element) {return $this->dqi->wrap($element);}
  public function wrapAll($element) {return $this->dqi->wrapAll($element);}
  public function wrapInner($element) {return $this->dqi->wrapInner($element);}
  public function clear() {return $this->dqi->clear();}
  public function removeAll($selector) {return $this->dqi->removeAll($selector);}
  public function replaceWith($something) {return $this->dqi->replaceWith($something);}
  public function replaceAll($selector) {return $this->dqi->replaceAll($selector);}
  public function attr($name, $value) {return $this->dqi->attr($selector);}
  public function removeAttr($name) {return $this->dqi->removeAttr($selector);}
  public function addClass($class) {return $this->dqi->addClass($class);}
  public function removeClass($class) {return $this->dqi->remove($class);}
  public function hasClass($class) {return $this->dqi->hasClass($class);}
  public function cloneE() {return $this->dqi->cloneE();}
  public function serialize() {return $this->dqi->serialize();}
  public function serializeArray() {return $this->dqi->serializeArray();}
}
