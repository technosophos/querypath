<?php
/**
 * QPTPL is a template library for QueryPath.
 *
 * The QPTPL extension provides template tools that can be used in 
 * conjunction with QueryPath.
 *
 * @package QueryPath
 * @subpackage Extension
 * @author M Butcher <matt@aleph-null.tv>
 * @license LGPL or MIT-like license.
 * @see QueryPathExtension
 * @see QueryPathExtensionRegistry::extend()
 */

class QPTPL implements QueryPathExtension {
  protected $qp;
  public function __construct(QueryPath $qp) {
    $this->qp = $qp;
  }
  
  /**
   * Apply a template to an object and then insert the results.
   *
   * This takes a template (an arbitrary fragment of XML/HTML) and an object
   * or array and inserts the contents of the object into the template. The 
   * template is then appended to all of the nodes in the current list.
   *
   * Note that the data in the object is *not* escaped before it is merged 
   * into the template. For that reason, an object can return markup (as 
   * long as it is well-formed).
   * 
   * @param mixed $template
   *  The template. It can be of any of the types that {@link qp()} supports
   *  natively. Typically it is a string of XML/HTML.
   * @param mixed $object
   *  Either an object or an associative array. 
   *  - In the case where the parameter
   *  is an object, this will introspect the object, looking for getters (a la
   *  Java bean behavior). It will then search the document for CSS classes
   *  that match the method name. The function is then executed and its contents
   *  inserted into the document. (If the function returns NULL, nothing is 
   *  inserted.)
   *  - In the case where the paramter is an associative array, the function will
   *  look through the template for CSS classes that match the keys of the 
   *  array. When an array key is found, the array value is inserted into the 
   *  DOM as a child of the currently matched element(s).
   * @param array $options
   *  The options for this function. Valid options are:
   *  - <None defined yet>
   * @return QueryPath
   *  Returns a QueryPath object with all of the changes from the template
   *  applied into the QueryPath elements.
   * @see QueryPath::append()
   */
  public function tpl($template, $object, $options = array()) {
    // Handle default options here.

    //$tqp = ($template instanceof QueryPath) ? clone $template: qp($template);
    $tqp = qp($template);
    
    if (is_array($object)) $this->tplArray($tqp, $object, $options);
    elseif (is_object($object)) $this->tplObject($tqp, $object, $options);
    
    return $this->qp->append($tqp);
  }
  
  /**
   * Given one template, do substitutions for all objects.
   *
   * Using this method, one template can be populated from a variety of 
   * sources. That one template is then appended to the QueryPath object.
   * @see tpl()
   * @param mixed $template
   *  The template. It can be of any of the types that {@link qp()} supports
   *  natively. Typically it is a string of XML/HTML.
   * @param array $objects
   *  An indexed array containing a list of objects or arrays (See {@link tpl()})
   *  that will be merged into the template.
   * @param array $options
   *  An array of options. See {@link tpl()} for a list.
   * @return QueryPath
   *  Returns the QueryPath object.
   */
  public function tplAll($template, $objects, $options = array()) {
    $tqp = qp($template, ':root');
    foreach ($objects as $object) {
      if (is_array($object)) 
        $tqp = $this->tplArray($tqp, $object, $options);
      elseif (is_object($object)) 
        $tqp = $this->tplObject($tqp, $object, $options);
    }
    return $this->qp->append($tqp);
  }
  
  protected function tplArray($tqp, $array, $options = array()) {
    foreach ($array as $key => $value) {
      $first = substr($key,0,1);
      
      // We allow classes and IDs if explicit. Otherwise we assume
      // a class.
      if ($first != '.' && $first != '#') $key = '.' . $key;
      // Breaking the find into two steps is faster.
      if ($tqp->top()->find($key)->size() > 0) {
        $tqp->append($value);
      }
    }
    return $tqp->top();
  }
  
  protected function tplObject($tqp, $object, $options = array()) {
    $ref = new ReflectionObject($object);
    $methods = $ref->getMethods();
    foreach ($methods as $method) {
      if (strpos($method->getName(), 'get') === 0) {
        $cssClass = $this->method2class($method->getName());
        if ($tqp->top()->find($cssClass)->size() > 0) {
          $tqp->append($method->invoke($object));
        }
        else {
          // Revert to the find() that found something.
          $tqp->end();
        }
      }
    }
    return $tqp->top();
  }
  
  protected function method2class($mname) {
    return '.' . substr($mname, 3);
  }
}
QueryPathExtensionRegistry::extend('QPTPL');