<?php
require_once 'PHPUnit/Framework.php';
require_once '../src/QueryPath/QueryPath.php';

/**
 * Tests for DOM Query. Primarily, this is focused on the DomQueryImpl
 * class which is exposed through the DomQuery interface and the dq() 
 * factory function.
 */
class QueryPathTests extends PHPUnit_Framework_TestCase {
  
  public function testQueryPathConstructors() {
    $file = './data.xml';
    $qp = qp($file);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    $str = '<?xml version="1.0" ?><root><inner/></root>';
    $qp = qp($str);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);

    $str = '<?xml version="1.0" ?><root><inner/></root>';    
    $qp = qp(simplexml_load_string($str));
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    $qp = qp(DOMDocument::loadXML($str));
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // Now with a selector:
    $qp = qp($file, '#head');
    $this->assertEquals(1, count($qp->get()));
    $this->assertEquals($qp->get(0)->tagName, 'head');
    
    // Test HTML:
    $htmlFile = './data.html';
    $qp = qp($htmlFile);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
  }
  
  public function testFind() {
    $file = './data.xml';
    $qp = qp($file)->find('#head');
    $this->assertEquals(1, count($qp->get()));
    $this->assertEquals($qp->get(0)->tagName, 'head');
  }
  
  public function testAttr() {
    $file = './data.xml';
    
    $qp = qp($file)->find('#head');
    //$this->assertEquals(1, $qp->size());
    $this->assertEquals($qp->get(0)->getAttribute('id'), $qp->attr('id'));
    
    $qp->attr('foo', 'bar');
    $this->assertEquals('bar', $qp->attr('foo'));
    
    $qp->attr(array('foo2' => 'bar', 'foo3' => 'baz'));
    $this->assertEquals('baz', $qp->attr('foo3'));
    
  }
  
  public function testEq() {
    $file = './data.xml';
    $qp = qp($file)->find('li')->eq(0);
    $this->assertEquals(1, $qp->size());
    $this->assertEquals($qp->attr('id'), 'one');
  }
  
  public function testIs() {
    $file = './data.xml';
    $this->assertTrue(qp($file)->find('#one')->is('#one'));
    $this->assertTrue(qp($file)->find('li')->is('#one'));
  }
  
  public function testFilter() {
    $file = './data.xml';
    $this->assertEquals(1, qp($file)->filter('li')->size());
    $this->assertEquals(2, qp($file, 'inner')->filter('li')->size());
    $this->assertEquals('inner-two', qp($file, 'inner')->filter('li')->eq(1)->attr('id'));
  }
  
  public function testFilterLambda() {
    $file = './data.xml';
    // Get all evens:
    $l = 'return (($index + 1) % 2 == 0);';
    $this->assertEquals(2, qp($file, 'li')->filterLambda($l)->size());
  }
  
  public function filterCallbackFunction($index, $item) {
    return (($index + 1) % 2 == 0);
  }
  
  public function testFilterCallback() {
    $file = './data.xml';
    $cb = array($this, 'filterCallbackFunction');
    $this->assertEquals(2, qp($file, 'li')->filterCallback($cb)->size());
  }
  
  public function testSlice() {
    $file = './data.xml';
    // There are five <li> elements
    $this->assertEquals(4, qp($file, 'li')->slice(1)->size());
    
    // This should not throw an error.
    $this->assertEquals(4, qp($file, 'li')->slice(1, 9)->size());
    
    $this->assertEquals(0, qp($file, 'li')->slice(9)->size());
    
    $this->assertEquals(2, qp($file, 'li')->slice(1, 2)->size());
  }
  
  public function mapCallbackFunction($index, $item) {
    if ($index == 1) {
      return FALSE;
    }
    if ($index == 2) {
      return array(1, 2, 3);
    }
    return $index;
  }
  
  public function testMap() {
    $file = './data.xml';
    $fn = 'mapCallbackFunction';
    $this->assertEquals(7, qp($file, 'li')->map(array($this, $fn))->size());
  }
  
  public function eachCallbackFunction($index, &$item) {
    if ($index < 2) {
      qp($item)->attr('class', 'test');
    }
    else {
      return FALSE;
    }
  }
  
  public function testEach() {
    $file = './data.xml';
    $fn = 'eachCallbackFunction';
    $res = qp($file, 'li')->each(array($this, $fn));
    $this->assertEquals(5, $res->size());
    $this->assertFalse($res->get(4)->getAttribute('class') === NULL);
    $this->assertEquals('test', $res->eq(1)->attr('class'));
  }
  
  public function testEachLambda() {
    $file = './data.xml';
    $fn = 'qp($item)->attr("class", "foo");';
    $res = qp($file, 'li')->eachLambda($fn);
    $this->assertEquals('foo', $res->eq(1)->attr('class'));
  }
  
  public function testDeepest() {
    $str = '<?xml version="1.0" ?>
    <root>
      <one/>
      <one><two/></one>
      <one><two><three/></two></one>
      <one><two><three><four/></three></two></one>
      <one/>
      <one><two><three><banana/></three></two></one>
    </root>';
    $deepest = qp($str)->deepest();
    $this->assertEquals(2, $deepest->size());
    $this->assertEquals('four', $deepest->get(0)->tagName);
    $this->assertEquals('banana', $deepest->get(1)->tagName);
    
    $deepest = qp($str, 'one')->deepest();
    $this->assertEquals(2, $deepest->size());
    $this->assertEquals('four', $deepest->get(0)->tagName);
    $this->assertEquals('banana', $deepest->get(1)->tagName);
    
    $str = '<?xml version="1.0" ?>
    <root>
      CDATA
    </root>';
    $this->assertEquals(1, qp($str)->deepest()->size());
  }
  
  public function testTag() {
    $file = './data.xml';
    $this->assertEquals('li', qp($file, 'li')->tag());
  }
  
  public function testAppend() {
    $file = './data.xml';
    $this->assertEquals(1, qp($file,'unary')->append('test')->find(':root > unary > test')->size());
    $qp = qp($file,'#inner-one')->append('<li id="appended"/>');
    $this->assertEquals(1, $qp->find('#appended')->size());
    $this->assertNull($qp->get(0)->nextSibling);
  }
  
  public function testPrepend() {
    $file = './data.xml';
    $this->assertEquals(1, qp($file,'unary')->prepend('test')->find(':root > unary > test')->size());
    $qp = qp($file,'#inner-one')->prepend('<li id="appended"/>')->find('#appended');
    $this->assertEquals(1, $qp->size());
    $this->assertNull($qp->get(0)->previousSibling);
  }
  
  public function testWrap() {
    $file = './data.xml';
    $xml = qp($file,'unary')->wrap('<test id="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, qp($xml, '#testWrap')->get(0)->childNodes->length);
    
    $xml = qp($file,'li')->wrap('<test class="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap')->size());
  }
  
  public function testWrapAll() {
    
  }
  
  public function testWrapInner() {
    
    
  }
}