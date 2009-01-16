<?php
/**
 * This extension provides support for common HTML list operations.
 * @package QueryPath
 * @subpackage Extension
 */

/**
 * Provide list operations for QueryPath.
 *
 * The QPList class is an extension to QueryPath. It provides HTML list generators
 * that take lists and convert them into bulleted lists inside of QueryPath.
 */ 
class QPList implements QueryPathExtension {
  const UL = 'ul';
  const OL = 'ol';
  const DL = 'dl';
  
  protected $qp = NULL;
  public function __construct(QueryPath $qp) {
    $this->qp = $qp;
  }
  
  /**
   * Append a list of items into an HTML DOM using one of the HTML list structures.
   * This takes a one-dimensional array and converts it into an HTML UL or OL list,
   * <b>or</b> it can take an associative array and convert that into a DL list.
   *
   * In addition to arrays, this works with any Traversable or Iterator object.
   *
   * OL/UL arrays can be nested.
   *
   * @param mixed $items
   *   An indexed array for UL and OL, or an associative array for DL. Iterator and
   *  Traversable objects can also be used.
   * @param string $type
   *  One of ul, ol, or dl. Predefined constants are available for use.
   * @param array $options
   *  An associative array of configuration options. The supported options are:
   *  - 'list class': The class that will be assigned to a list.
   */
  public function appendList($items, $type = self::UL, $options = array()) {
    $opts = $options + array(
      'list class' => 'qplist',
    );
    if ($type == self::DL) {
      $q = qp('<?xml version="1.0"?><dl></dl>', 'dl')->addClass($opts['list class']);
      foreach ($items as $dt => $dd) {
        $q->append('<dt>' . $dt . '</dt><dd>' . $dd . '</dd>');
      }
      $q->appendTo($this->qp);
    }
    else {
      $q = $this->listImpl($items, $type, $opts);
      $this->qp->append($q->find(':root'));
    }
    
    return $this->qp;
  }
  
  /**
   * Internal recursive list generator for appendList.
   */
  protected function listImpl($items, $type, $opts, $q = NULL) {
    $ele = '<' . $type . '/>';
    if (!isset($q))
      $q = qp()->append($ele)->addClass($opts['list class']);
          
    foreach ($items as $li) {
      if ($li instanceof QueryPath) {
        $q = $this->listImpl($li->get(), $type, $opts, $q);
      }
      elseif (is_array($li) || $li instanceof Traversable || $li instanceof Iterator) {
        $q->append('<li><ul/></li>')->find('li:last > ul');
        $q = $this->listImpl($li, $type, $opts, $q);
        $q->parent();
      }
      else {
        $q->append('<li>' . $li . '</li>');
      }
    }
    return $q;
  }
  
  /**
   * Unused.
   */
  protected function isAssoc($array) {
    // A clever method from comment on is_array() doc page:
    return count(array_diff_key($array, range(0, count($array) - 1))) != 0; 
  }
}
QueryPathExtensionRegistry::extend('QPList');