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
 *
 * How this class works:
 * 
 * The extension mechanism for QueryPath uses a Decorator pattern.
 * Essentially, each extension creates a new wrapper around the main QueryPath
 * implementation. This class provides the generic decorator implementation. All other
 * extensions should extend this class.
 *
 * Since extensions are decorators, they can reliably trap and override the behavior
 * of functions. So you gain the main advantages of inheritance. But You also gain two
 * major additional advantages: You get what equates to 'multiple inheritance'. Many
 * different extensions can wrap a single QueryPath, essentially combining many
 * QueryPath-like objects.
 *
 * The second advantage is explicit orderability. You can determine based on order of
 * import which extensions will be fired last (first imported) and which will be fired
 * first (last imported). This gives you an additional degree of control.
 * 
 * Of course, all of the downsides to multiple inheritance are present in a decorator
 * pattern: You can have undefined behaviors when multiple decorator classes override
 * the same base method. You can also have difficult-to-debug issues when many
 * different classes *could* be responsible for errant behavior.
 */
class QueryPathExtender implements QueryPath {
  // We don't use the magic __call() becase of overhead and complexity. It triples
  // callback time and also requires ugly logic to make it work with all functions.
  
  /**
   * Internal flag indicating whether or not the registry should
   * be used for automatic extension loading. If this is false, then
   * implementations should not automatically load extensions.
   */
  public static $useRegistry = TRUE;
  /**
   * The extension registry. This should consist of an array of class
   * names.
   */
  protected static $extensionRegistry = array();
  /**
   * Extend QueryPath with the given extension class.
   */
  public static function extend($classname) {
    self::$extensionRegistry[] = $classname;
  }
  
  public static function getExtensions() {
    return self::$extensionRegistry;
  }
  /**
   * Enable or disable automatic extension loading.
   *
   * If extension autoloading is disabled, then QueryPath will not 
   * automatically load all registred extensions when a new QueryPath
   * object is created using {@link qp()}.
   */
  public static function autoloadExtensions($boolean = TRUE) {
    self::$useRegistry = $boolean;
  }
  

  
  private $qpi = NULL;
  /**
   * Create a new instance of this QueryPathExtension. 
   *
   * This and all instance methods in the class are used to decorate an existing
   * QueryPath object. The construct takes a QueryPath instance and wraps it for
   * internal use. By default, all implementations of QueryPath in this class
   * simply pass data through to the underlying QueryPath instance. You may
   * extend this class and selectively override if you want custom behavior. 
   * More likely, though, you will want to just define your own new methods that
   * will be added to the object returned by {@link qp()}.
   */
  public function __construct(QueryPath $qp) {
    $this->qpi = $qp;
  }
  public function __clone() {
    $this->qpi = clone $this->qpi;
  }
  public function find($selector) {$this->qpi->find($selector); return $this;}
  public function size() {return $this->qpi->size();}
  public function get($index = NULL){return $this->qpi->get($index);}
  public function eq($index){$this->qpi->eq($index); return $this;}
  public function attr($name, $value = NULL){
    $t = $this->qpi->attr($name, $value);
    return ($t instanceof QueryPath) ? $this : $t;
  }
  public function removeAttr($name){$this->qpi->removeAttr($name); return $this;}
  public function is($selector){return $this->qpi->is($selector);}
  public function filter($selector){$this->qpi->filter($selector); return $this;}
  public function filterLambda($function){$this->qpi->filterLambda($function); return $this;}
  public function filterCallback($callback){$this->qpi->filterCallback($callback); return $this;}
  public function not($selector){$this->qpi->not($selector); return $this;}
  public function index($subject){$this->qpi->index($subject); return $this;}
  public function map($callback){$this->qpi->map($callback); return $this;}
  public function slice($start, $end = NULL){$this->qpi->slice($start, $end); return $this;}
  public function each($callback){$this->qpi->each($callback); return $this;}
  public function eachLambda($lambda){$this->qpi->eachLambda($lambda); return $this;}
  public function append($apendage){$this->qpi->append($apendage); return $this;}
  public function appendTo(QueryPath $destination){$this->qpi->appendTo($destination); return $this;}
  public function insertAfter(QueryPath $dest){$this->qpi->insertAfter($dest); return $this;}
  public function after($data){$this->qpi->after($data); return $this;}
  public function insertBefore(QueryPath $dest){$this->qpi->insertBefore($dest); return $this;}
  public function before($data){$this->qpi->before($data); return $this;}
  public function prepend($prependage){$this->qpi->prepend($prependage); return $this;}
  public function prependTo(QueryPath $dest){$this->qpi->prependTo($dest); return $this;}
  public function deepest(){$this->qpi->deepest(); return $this;}
  public function wrap($markup){$this->qpi->wrap($markup); return $this;}
  public function wrapAll($markup){$this->qpi->wrapAll($markup); return $this;}
  public function wrapInner($element){$this->qpi->wrapInner($element); return $this;}
  public function tag(){return $this->qpi->tag();}
  public function replaceWith($new){$this->qpi->replaceWith($new); return $this;}
  public function remove($selector = NULL){$this->qpi->remove($selector); return $this;}
  public function replaceAll($selector, DOMDocument $document){$this->qpi->replaceAll($selector, $document); return $this;}
  public function add($selector){$this->qpi->add($selector); return $this;}
  public function end(){$this->qpi->end(); return $this;}
  public function andSelf(){$this->qpi->andSelf(); return $this;}
  public function removeChildren(){$this->qpi->removeChildren(); return $this;}
  public function children($selector = NULL){$this->qpi->children($selector); return $this;}
  public function contents(){
    $t = $this->qpi->contents();
    return ($t instanceof QueryPath) ? $this : $t;
  }
  public function html($markup = NULL){
    $t = $this->qpi->html($markup);
    return ($t instanceof QueryPath) ? $this : $t;
  }
  public function text($text = NULL){
    $t = $this->qpi->text($text);
    return ($t instanceof QueryPath) ? $this : $t;
  }
  public function xml($markup = NULL){
    $t = $this->qpi->xml($markup);
    return ($t instanceof QueryPath) ? $this : $t;
  }
  public function writeXML(){return $this->qpi->writeXML();}
  public function writeHTML(){return $this->qpi->writeHTML();}
  public function val($value = NULL){return $this->qpi->val($value);}
  public function siblings($selector = NULL){$this->qpi->siblings($selector); return $this;}
  public function next($selector = NULL){$this->qpi->next($selector); return $this;}
  public function nextAll($selector = NULL){$this->qpi->nextAll($selector); return $this;}
  public function prev($selector = NULL){$this->qpi->prev($selector); return $this;}
  public function prevAll($selector = NULL){$this->qpi->prevAll($selector); return $this;}
  public function parent($selector = NULL){$this->qpi->parent($selector); return $this;}
  public function parents($selector = NULL){$this->qpi->parents($selector); return $this;}
  public function addClass($class){$this->qpi->addClass($class); return $this;}
  public function removeClass($class){$this->qpi->removeClass($class); return $this;}
  public function hasClass($class){return $this->qpi->hasClass($class);}
  public function cloneAll(){$this->qpi->cloneAll(); return $this;}
  public function css($name = NULL, $value = ''){
    $t = $this->qpi->css($name, $value);
    return ($t instanceof QueryPath) ? $this : $t;
  }
}
