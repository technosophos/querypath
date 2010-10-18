<?php
/**
 * Tests for the QueryPath library.
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once 'src/QueryPath/QueryPath.php';
require_once 'src/QueryPath/Extension/QPList.php';

/**
 * @ingroup querypath_tests
 */
class QPListTests extends PHPUnit_Framework_TestCase {
  public function testAppendList() {
    $list = array('one', 'two', 'three');
    $qp = qp(QueryPath::HTML_STUB, 'body')->appendList($list, QPList::UL);
    $this->assertEquals(3, $qp->find(':root ul>li')->size());
    $this->assertEquals('one', $qp->find(':root ul>li:first')->text());
    
    $list = array('zero-one','two','three', array('four-one', 'four-two', array('four-three-one', 'four-three-two')));
    $qp = qp(QueryPath::HTML_STUB, 'body')->appendList($list, QPList::UL);
    $this->assertEquals(4, $qp->find(':root .qplist>li')->size());
    // Find bottom layer of recursive tree.
    $this->assertEquals(2, $qp->find(':root ul>li>ul>li>ul>li')->size());
    
    // Assoc array tests...
    $list = array('a' => 'aa', 'b' => 'bb', 'c' => 'cc');
    $qp = qp(QueryPath::HTML_STUB, 'body')->appendList($list, QPList::UL);
    $this->assertEquals('aa', $qp->find(':root .qplist>li:first')->text());
    
    $qp = qp(QueryPath::HTML_STUB, 'body')->appendList($list, QPList::DL);
    $this->assertEquals('a', $qp->find(':root .qplist>dt:first')->text());
    $this->assertEquals('aa', $qp->find(':root .qplist>dd:first')->text());
    //$qp->writeXML();
  }
  
  public function testAppendTable() {
    $data = array(
      'headers' => array('One', 'Two', 'Three'),
      'rows' => array(
        array(1, 2, 3),
        array('Ein', 'Zwei', 'Drei'),
        array('uno', 'dos', 'tres'),
        array('uno', 'du'), // See what happens here...
      ),
    );
    $qp = qp(QueryPath::HTML_STUB, 'body')->appendTable($data);
    $this->assertEquals(3, $qp->top()->find('th')->size());
    $this->assertEquals(11, $qp->top()->find('td')->size());
    $this->assertEquals('Zwei', $qp->eq(4)->text());
    
    // Test with an object instead...
    $o = new QPTableData();
    $o->setHeaders($data['headers']);
    $o->setRows($data['rows']);
    $qp = qp(QueryPath::HTML_STUB, 'body')->appendTable($o);
    $this->assertEquals(3, $qp->top()->find('th')->size());
    $this->assertEquals(11, $qp->top()->find('td')->size());
    $this->assertEquals('Zwei', $qp->eq(4)->text());
  }
}