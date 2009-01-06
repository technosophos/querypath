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
  
  public function testRemoveAttr() {
    $file = './data.xml';
    
    $qp = qp($file, 'inner')->removeAttr('class');
    $this->assertEquals(2, $qp->size());
    $this->assertFalse($qp->get(0)->hasAttribute('class'));
    
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
    $this->assertEquals(1, qp($file,'unary')->append('<test/>')->find(':root > unary > test')->size());
    $qp = qp($file,'#inner-one')->append('<li id="appended"/>');
    $this->assertEquals(1, $qp->find('#appended')->size());
    $this->assertNull($qp->get(0)->nextSibling);
  }
  
  public function testAppendTo() {
    $file = './data.xml';
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->appendTo($dest);
    $this->assertEquals(5, $dest->find(':root li')->size());
  }
  
  public function testPrepend() {
    $file = './data.xml';
    $this->assertEquals(1, qp($file,'unary')->prepend('<test/>')->find(':root > unary > test')->size());
    $qp = qp($file,'#inner-one')->prepend('<li id="appended"/>')->find('#appended');
    $this->assertEquals(1, $qp->size());
    $this->assertNull($qp->get(0)->previousSibling);
  }
  
  public function testPrependTo() {
    $file = './data.xml';
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->prependTo($dest);
    $this->assertEquals(5, $dest->find(':root li')->size());
  }
  
  public function testBefore() {
    $file = './data.xml';
    $this->assertEquals(1, qp($file,'unary')->before('<test/>')->find(':root > unary ~ test')->size());
    $this->assertEquals('unary', qp($file,'unary')->before('<test/>')->find(':root > test')->get(0)->nextSibling->tagName);
  }
  
  public function testAfter() {
    $file = './data.xml';
    $this->assertEquals(1, qp($file,'unary')->after('<test/>')->find(':root > unary ~ test')->size());
    $this->assertEquals('unary', qp($file,'unary')->after('<test/>')->find(':root > test')->get(0)->previousSibling->tagName);
  }
  
  public function testInsertBefore() {
    $file = './data.xml';
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->insertBefore($dest);
    $this->assertEquals(5, $dest->find(':root > li')->size());
    $this->assertEquals('li', $dest->end()->find('dest')->get(0)->previousSibling->tagName);
  }
  public function testInsertAfter() {
    $file = './data.xml';
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->insertAfter($dest);
    //print $dest->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, $dest->find(':root > li')->size());
  }
  public function testReplaceWith() {
    $file = './data.xml';
    $qp = qp($file,'unary')->replaceWith('<test><foo/></test>')->find(':root test');
    //print $qp->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, $qp->size());
  }
  
  public function testReplaceAll() {
    // TODO: write unit test for this.
  }
  
  public function testWrap() {
    $file = './data.xml';
    $xml = qp($file,'unary')->wrap('<test id="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, qp($xml, '#testWrap')->get(0)->childNodes->length);
    
    $xml = qp($file,'li')->wrap('<test class="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap')->size());
    
    $xml = qp($file,'li')->wrap('<test class="testWrap"><inside><center/></inside></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap > inside > center > li')->size());
  }
  
  public function testWrapAll() {
    $file = './data.xml';
    $xml = qp($file,'unary')->wrapAll('<test id="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, qp($xml, '#testWrap')->get(0)->childNodes->length);
    
    $xml = qp($file,'li')->wrapAll('<test class="testWrap"><inside><center/></inside></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap > inside > center > li')->size());
    
  }
  
  public function testWrapInner() {
    $file = './data.xml';
    $xml = qp($file,'#inner-one')->wrapInner('<test class="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    // FIXME: 9 includes text nodes. Should fix this.
    $this->assertEquals(9, qp($xml, '.testWrap')->get(0)->childNodes->length);
  }
  
  public function testRemove() {
    $file = './data.xml';
    $qp = qp($file, 'li');
    $start = $qp->size();
    $finish = $qp->remove()->size();
    $this->assertEquals($start, $finish);
    $this->assertEquals(0, $qp->find(':root li')->size());
  }
  
  public function testHasClass() {
    $file = './data.xml';
    $this->assertTrue(qp($file, '#inner-one')->hasClass('innerClass'));
  }
  
  public function testAddClass() {
    $file = './data.xml';
    $this->assertTrue(qp($file, '#inner-one')->addClass('testClass')->hasClass('testClass'));
  }
  public function testRemoveClass() {
    $file = './data.xml';
    // The add class tests to make sure that this works with multiple values.
    $this->assertFalse(qp($file, '#inner-one')->removeClass('innerClass')->hasClass('innerClass'));
    $this->assertTrue(qp($file, '#inner-one')->addClass('testClass')->removeClass('innerClass')->hasClass('testClass'));
  }
  
  public function testAdd() {
    $file = './data.xml';
    $this->assertEquals(7, qp($file, 'li')->add('inner')->size());
  }
  
  public function testEnd() {
    $file = './data.xml';
    $this->assertEquals(2, qp($file, 'inner')->find('li')->end()->size());
  }
  
  public function testAndSelf() {
    $file = './data.xml';
    $this->assertEquals(7, qp($file, 'inner')->find('li')->andSelf()->size());
  }
  
  public function testChildren() {
    $file = './data.xml';
    $this->assertEquals(5, qp($file, 'inner')->children()->size());
    $this->assertEquals(5, qp($file, 'inner')->children('li')->size());
    $this->assertEquals(1, qp($file, ':root')->children('unary')->size());
  }
  public function testRemoveChildren() {
    $file = './data.xml';
    $this->assertEquals(0, qp($file, '#inner-one')->removeChildren()->find('li')->size());
  }
  
  public function testContents() {
    $file = './data.xml';
    $this->assertGreaterThan(5, qp($file, 'inner')->contents()->size());
    // Two cdata nodes and one element node.
    $this->assertEquals(3, qp($file, '#inner-two')->contents()->size());
  }
  
  public function testSiblings() {
    $file = './data.xml';
    $this->assertEquals(3, qp($file, '#one')->siblings()->size());
    $this->assertEquals(2, qp($file, 'unary')->siblings('inner')->size());
  }
  
  public function testHTML() {
    $file = './data.xml';
    $qp = qp($file, 'unary');
    $html = '<b>test</b>';
    $this->assertEquals($html, $qp->html($html)->find('b')->html());
    
    $html = '<html><head><title>foo</title></head><body>bar</body></html>';
    // We expect a DocType to be prepended:
    $this->assertEquals('<!DOCTYPE', substr(qp($html)->html(), 0, 9));
  }
  
  public function testXML() {
    $file = './data.xml';
    $qp = qp($file, 'unary');
    $xml = '<b>test</b>';
    $this->assertEquals($xml, $qp->xml($xml)->find('b')->xml());
    
    $xml = '<html><head><title>foo</title></head><body>bar</body></html>';
    // We expect a DocType to be prepended:
    $this->assertEquals('<?xml', substr(qp($xml, 'html')->xml(), 0, 5));
  }
  
  public function testWriteXML() {
    $xml = '<?xml version="1.0"?><html><head><title>foo</title></head><body>bar</body></html>';
    
    if (!ob_start()) die ("Could not start OB.");
    qp($xml, 'tml')->writeXML();
    $out = ob_get_contents();
    ob_end_clean();
    
    // We expect an XML declaration at the top.
    $this->assertEquals('<?xml', substr($out, 0, 5));
  }
  
  public function testWriteHTML() {
    $xml = '<html><head><title>foo</title></head><body>bar</body></html>';
    
    if (!ob_start()) die ("Could not start OB.");
    qp($xml, 'tml')->writeHTML();
    $out = ob_get_contents();
    ob_end_clean();
    
    // We expect a doctype declaration at the top.
    $this->assertEquals('<!DOC', substr($out, 0, 5));
  }
  
  /*
  public function testSerialize() {
    $file = './data.xml';
    $ser = qp($file)->serialize();
    print $ser;
    $qp = unserialize($ser);
    $this->assertEquals('inner-one', $qp->find('#inner-one')->attr('id'));
  }
  */
}