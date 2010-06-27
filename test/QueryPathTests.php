<?php
/**
 * Tests for the QueryPath library.
 * @package Tests
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once 'C:/xampp/htdocs/querypath/src/QueryPath/QueryPath.php';

define('DATA_FILE', 	 'C:/xampp/htdocs/querypath/test/data.xml');
define('DATA_HTML_FILE', 'C:/xampp/htdocs/querypath/test/data.html');
/**
 * Tests for DOM Query. Primarily, this is focused on the DomQueryImpl
 * class which is exposed through the DomQuery interface and the dq() 
 * factory function.
 */
class QueryPathTests extends PHPUnit_Framework_TestCase {
  
  public function testQueryPathConstructors() {
    
    // From XML file
    $file = DATA_FILE;
    $qp = qp($file);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // From XML string
    $str = '<?xml version="1.0" ?><root><inner/></root>';
    $qp = qp($str);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // From SimpleXML
    $str = '<?xml version="1.0" ?><root><inner/></root>';    
    $qp = qp(simplexml_load_string($str));
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // Test from DOMDocument
    $qp = qp(DOMDocument::loadXML($str));
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // Now with a selector:
    $qp = qp($file, '#head');
    $this->assertEquals(1, count($qp->get()));
    $this->assertEquals($qp->get(0)->tagName, 'head');
    
    // Test HTML:
    $htmlFile = DATA_HTML_FILE;
    $qp = qp($htmlFile);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // Test with another QueryPath
    $qp = qp($qp);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
    
    // Test from array of DOMNodes
    $array = $qp->get();
    $qp = qp($array);
    $this->assertEquals(1, count($qp->get()));
    $this->assertTrue($qp->get(0) instanceof DOMNode);
  }
  
  public function testFind() {
    $file = DATA_FILE;
    $qp = qp($file)->find('#head');
    $this->assertEquals(1, count($qp->get()));
    $this->assertEquals($qp->get(0)->tagName, 'head');
    
    $this->assertEquals('inner', qp($file)->find('.innerClass')->tag());
  }
  
  public function testTop() {
    $file = DATA_FILE;
    $qp = qp($file)->find('li');
    $this->assertGreaterThan(2, $qp->size());
    $this->assertEquals(1, $qp->top()->size());
  }
  
  public function testAttr() {
    $file = DATA_FILE;
    
    $qp = qp($file)->find('#head');
    //$this->assertEquals(1, $qp->size());
    $this->assertEquals($qp->get(0)->getAttribute('id'), $qp->attr('id'));
    
    $qp->attr('foo', 'bar');
    $this->assertEquals('bar', $qp->attr('foo'));
    
    $qp->attr(array('foo2' => 'bar', 'foo3' => 'baz'));
    $this->assertEquals('baz', $qp->attr('foo3'));
    
    // Check magic nodeType attribute:
    $this->assertEquals(XML_ELEMENT_NODE, qp($file)->find('#head')->attr('nodeType'));
    
  }
  
  public function testCss() {
    $file = DATA_FILE;
    $this->assertEquals('foo: bar', qp($file, 'unary')->css('foo', 'bar')->attr('style'));
    $this->assertEquals('foo: bar', qp($file, 'unary')->css('foo', 'bar')->css());
  }
  
  public function testRemoveAttr() {
    $file = DATA_FILE;
    
    $qp = qp($file, 'inner')->removeAttr('class');
    $this->assertEquals(2, $qp->size());
    $this->assertFalse($qp->get(0)->hasAttribute('class'));
    
  }
  
  public function testEq() {
    $file = DATA_FILE;
    $qp = qp($file)->find('li')->eq(0);
    $this->assertEquals(1, $qp->size());
    $this->assertEquals($qp->attr('id'), 'one');
  }
  
  public function testIs() {
    $file = DATA_FILE;
    $this->assertTrue(qp($file)->find('#one')->is('#one'));
    $this->assertTrue(qp($file)->find('li')->is('#one'));
  }
  
  public function testFilter() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file)->filter('li')->size());
    $this->assertEquals(2, qp($file, 'inner')->filter('li')->size());
    $this->assertEquals('inner-two', qp($file, 'inner')->filter('li')->eq(1)->attr('id'));
  }
  
  public function testFilterLambda() {
    $file = DATA_FILE;
    // Get all evens:
    $l = 'return (($index + 1) % 2 == 0);';
    $this->assertEquals(2, qp($file, 'li')->filterLambda($l)->size());
  }
  
  public function filterCallbackFunction($index, $item) {
    return (($index + 1) % 2 == 0);
  }
  
  public function testFilterCallback() {
    $file = DATA_FILE;
    $cb = array($this, 'filterCallbackFunction');
    $this->assertEquals(2, qp($file, 'li')->filterCallback($cb)->size());
  }
  
  public function testSlice() {
    $file = DATA_FILE;
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
    $file = DATA_FILE;
    $fn = 'mapCallbackFunction';
    $this->assertEquals(7, qp($file, 'li')->map(array($this, $fn))->size());
  }
  
  public function eachCallbackFunction($index, $item) {
    if ($index < 2) {
      qp($item)->attr('class', 'test');
    }
    else {
      return FALSE;
    }
  }
  
  public function testEach() {
    $file = DATA_FILE;
    $fn = 'eachCallbackFunction';
    $res = qp($file, 'li')->each(array($this, $fn));
    $this->assertEquals(5, $res->size());
    $this->assertFalse($res->get(4)->getAttribute('class') === NULL);
    $this->assertEquals('test', $res->eq(1)->attr('class'));
  }
  
  public function testEachLambda() {
    $file = DATA_FILE;
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
    $file = DATA_FILE;
    $this->assertEquals('li', qp($file, 'li')->tag());
  }
  
  public function testAppend() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file,'unary')->append('<test/>')->find(':root > unary > test')->size());
    $qp = qp($file,'#inner-one')->append('<li id="appended"/>');
    $this->assertEquals(1, $qp->find('#appended')->size());
    $this->assertNull($qp->get(0)->nextSibling);
    
    $this->assertEquals(2, qp($file, 'inner')->append('<test/>')->top()->find('test')->size());
    $this->assertEquals(2, qp($file, 'inner')->append(qp('<?xml version="1.0"?><test/>'))->top()->find('test')->size());
  }
  
  public function testAppendTo() {
    $file = DATA_FILE;
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->appendTo($dest);
    $this->assertEquals(5, $dest->find(':root li')->size());
  }
  
  public function testPrepend() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file,'unary')->prepend('<test/>')->find(':root > unary > test')->size());
    $qp = qp($file,'#inner-one')->prepend('<li id="appended"/>')->find('#appended');
    $this->assertEquals(1, $qp->size());
    $this->assertNull($qp->get(0)->previousSibling);
    
    // Test repeated insert
    $this->assertEquals(2, qp($file,'inner')->prepend('<test/>')->top()->find('test')->size());
  }
  
  public function testPrependTo() {
    $file = DATA_FILE;
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->prependTo($dest);
    $this->assertEquals(5, $dest->find(':root li')->size());
  }
  
  public function testBefore() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file,'unary')->before('<test/>')->find(':root > unary ~ test')->size());
    $this->assertEquals('unary', qp($file,'unary')->before('<test/>')->find(':root > test')->get(0)->nextSibling->tagName);
    
    // Test repeated insert
    $this->assertEquals(2, qp($file,'inner')->before('<test/>')->top()->find('test')->size());
  }
  
  public function testAfter() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file,'unary')->after('<test/>')->find(':root > unary ~ test')->size());
    $this->assertEquals('unary', qp($file,'unary')->after('<test/>')->find(':root > test')->get(0)->previousSibling->tagName);
    
    $this->assertEquals(2, qp($file,'inner')->after('<test/>')->top()->find('test')->size());
    
  }
  
  public function testInsertBefore() {
    $file = DATA_FILE;
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->insertBefore($dest);
    $this->assertEquals(5, $dest->find(':root > li')->size());
    $this->assertEquals('li', $dest->end()->find('dest')->get(0)->previousSibling->tagName);
  }
  public function testInsertAfter() {
    $file = DATA_FILE;
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = qp($file,'li')->insertAfter($dest);
    //print $dest->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, $dest->find(':root > li')->size());
  }
  public function testReplaceWith() {
    $file = DATA_FILE;
    $qp = qp($file,'unary')->replaceWith('<test><foo/></test>')->find(':root test');
    //print $qp->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, $qp->size());
  }
  
  public function testReplaceAll() {
    // TODO: write unit test for this.
  }
  
  public function testWrap() {
    $file = DATA_FILE;
    $xml = qp($file,'unary')->wrap('<test id="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, qp($xml, '#testWrap')->get(0)->childNodes->length);
    
    $xml = qp($file,'li')->wrap('<test class="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap')->size());
    
    $xml = qp($file,'li')->wrap('<test class="testWrap"><inside><center/></inside></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap > inside > center > li')->size());
  }
  
  public function testWrapAll() {
    $file = DATA_FILE;
    $xml = qp($file,'unary')->wrapAll('<test id="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(1, qp($xml, '#testWrap')->get(0)->childNodes->length);
    
    $xml = qp($file,'li')->wrapAll('<test class="testWrap"><inside><center/></inside></test>')->get(0)->ownerDocument->saveXML();
    $this->assertEquals(5, qp($xml, '.testWrap > inside > center > li')->size());
    
  }
  
  public function testWrapInner() {
    $file = DATA_FILE;
    $xml = qp($file,'#inner-one')->wrapInner('<test class="testWrap"></test>')->get(0)->ownerDocument->saveXML();
    // FIXME: 9 includes text nodes. Should fix this.
    $this->assertEquals(9, qp($xml, '.testWrap')->get(0)->childNodes->length);
  }
  
  public function testRemove() {
    $file = DATA_FILE;
    $qp = qp($file, 'li');
    $start = $qp->size();
    $finish = $qp->remove()->size();
    $this->assertEquals($start, $finish);
    $this->assertEquals(0, $qp->find(':root li')->size());
  }

  public function testDetach() {
    $file = DATA_FILE;
    $qp = qp($file, 'li');
    $start = $qp->size();
    $finish = $qp->detach()->size();
    $this->assertEquals($start, $finish);
    $this->assertEquals(0, $qp->find(':root li')->size());
    $dest = qp('<?xml version="1.0"?><root><dest/></root>', 'dest');
    $qp = $qp->attach($dest);
    $this->assertEquals(5, $dest->find(':root li')->size());
  }  
 
  public function testEmptyElement() {
    $file = DATA_FILE;
    $this->assertEquals(0, qp($file, '#inner-two')->emptyElement()->find('li')->size());
    $this->assertEquals('<inner id="inner-two"/>', qp($file, '#inner-two')->emptyElement()->html());
  }
  
  public function testHasClass() {
    $file = DATA_FILE;
    $this->assertTrue(qp($file, '#inner-one')->hasClass('innerClass'));
  }
  
  public function testHas() {
    $file = DATA_FILE;
    $selector = qp($file, 'foot');
    $this->assertEquals(qp($file, '#one')->children(), qp($file, '#inner-one')->has($selector));
    $qp = qp($file, 'root')->children("inner");
    $this->assertEquals(qp($file, 'root'), qp($file, 'root')->has($selector), "Should both have 1 element - root");
  }
  
  public function testAddClass() {
    $file = DATA_FILE;
    $this->assertTrue(qp($file, '#inner-one')->addClass('testClass')->hasClass('testClass'));
  }
  public function testRemoveClass() {
    $file = DATA_FILE;
    // The add class tests to make sure that this works with multiple values.
    $this->assertFalse(qp($file, '#inner-one')->removeClass('innerClass')->hasClass('innerClass'));
    $this->assertTrue(qp($file, '#inner-one')->addClass('testClass')->removeClass('innerClass')->hasClass('testClass'));
  }
  
  public function testAdd() {
    $file = DATA_FILE;
    $this->assertEquals(7, qp($file, 'li')->add('inner')->size());
  }
  
  public function testEnd() {
    $file = DATA_FILE;
    $this->assertEquals(2, qp($file, 'inner')->find('li')->end()->size());
  }
  
  public function testAndSelf() {
    $file = DATA_FILE;
    $this->assertEquals(7, qp($file, 'inner')->find('li')->andSelf()->size());
  }
  
  public function testChildren() {
    $file = DATA_FILE;
    $this->assertEquals(5, qp($file, 'inner')->children()->size());
    $this->assertEquals(5, qp($file, 'inner')->children('li')->size());
    $this->assertEquals(1, qp($file, ':root')->children('unary')->size());
  }
  public function testRemoveChildren() {
    $file = DATA_FILE;
    $this->assertEquals(0, qp($file, '#inner-one')->removeChildren()->find('li')->size());
  }
  
  public function testContents() {
    $file = DATA_FILE;
    $this->assertGreaterThan(5, qp($file, 'inner')->contents()->size());
    // Two cdata nodes and one element node.
    $this->assertEquals(3, qp($file, '#inner-two')->contents()->size());
  }
  
  public function testSiblings() {
    $file = DATA_FILE;
    $this->assertEquals(3, qp($file, '#one')->siblings()->size());
    $this->assertEquals(2, qp($file, 'unary')->siblings('inner')->size());
  }
  
  public function testHTML() {
    $file = DATA_FILE;
    $qp = qp($file, 'unary');
    $html = '<b>test</b>';
    $this->assertEquals($html, $qp->html($html)->find('b')->html());
    
    $html = '<html><head><title>foo</title></head><body>bar</body></html>';
    // We expect a DocType to be prepended:
    $this->assertEquals('<!DOCTYPE', substr(qp($html)->html(), 0, 9));
  }
  
  public function testXML() {
    $file = DATA_FILE;
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
  
  public function testText() {
    $xml = '<?xml version="1.0"?><root><div>Text A</div><div>Text B</div></root>';
    $this->assertEquals('Text AText B', qp($xml)->text());
    $this->assertEquals('Foo', qp($xml, 'div')->eq(0)->text('Foo')->text());
  }
  
  public function testNext() {
    $file = DATA_FILE;
    $this->assertEquals('inner', qp($file, 'unary')->next()->tag());
    $this->assertEquals('foot', qp($file, 'inner')->next()->eq(1)->tag());
    
    $this->assertEquals('foot', qp($file, 'unary')->next('foot')->tag());
  }
  public function testPrev() {
    $file = DATA_FILE;
    $this->assertEquals('head', qp($file, 'unary')->prev()->tag());
    $this->assertEquals('inner', qp($file, 'inner')->prev()->eq(1)->tag());
    $this->assertEquals('head', qp($file, 'foot')->prev('head')->tag());
  }
  public function testNextAll() {
    $file = DATA_FILE;
    $this->assertEquals(3, qp($file, '#one')->nextAll()->size());
    $this->assertEquals(2, qp($file, 'unary')->nextAll('inner')->size());
  }
  public function testNextUntil() {
    $file = DATA_FILE;
    $this->assertEquals(3, qp($file, '#one')->nextUntil()->size());
    $this->assertEquals(2, qp($file, 'li')->nextUntil('#three')->size());
  }
  public function testPrevAll() {
    $file = DATA_FILE;
    $this->assertEquals(3, qp($file, '#four')->prevAll()->size());
    $this->assertEquals(2, qp($file, 'foot')->prevAll('inner')->size());
  }
  public function testPrevUntil() {
    $file = DATA_FILE;
    $this->assertEquals(3, qp($file, '#four')->prevUntil()->size());
    $this->assertEquals(2, qp($file, 'foot')->prevUntil('unary')->size());
  }
  public function testEven() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file, 'inner')->even()->size());
    $this->assertEquals(2, qp($file, 'li')->even()->size());
  }
  public function testOdd() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file, 'inner')->odd()->size());
    $this->assertEquals(3, qp($file, 'li')->odd()->size());
  }
  public function testFirst() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file, 'inner')->first()->size());
    $this->assertEquals(1, qp($file, 'li')->first()->size());
    $this->assertEquals("Hello", qp($file, 'li')->first()->text());
  }
  public function testFirstChild() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file, '#inner-one')->firstChild()->size());
    $this->assertEquals("Hello", qp($file, '#inner-one')->firstChild()->text());
  }
  public function testLast() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file, 'inner')->last()->size());
    $this->assertEquals(1, qp($file, 'li')->last()->size());
    $this->assertEquals('', qp($file, 'li')->last()->text());
  }
  public function testLastChild() {
    $file = DATA_FILE;
    $this->assertEquals(1, qp($file, '#inner-one')->lastChild()->size());
    $this->assertEquals("Last", qp($file, '#inner-one')->lastChild()->text());
  }
  public function testParent() {
    $file = DATA_FILE;
    $this->assertEquals('root', qp($file, 'unary')->parent()->tag());
    $this->assertEquals('root', qp($file, 'li')->parent('root')->tag());
    $this->assertEquals(2, qp($file, 'li')->parent()->size());
  }
  public function testParents() {
    $file = DATA_FILE;
    
    // Three: two inners and a root.
    $this->assertEquals(3, qp($file, 'li')->parents()->size());
    $this->assertEquals('root', qp($file, 'li')->parents('root')->tag());
  }
  public function testParentsUntil() {
    $file = DATA_FILE;

    // Three: two inners and a root.
    $this->assertEquals(3, qp($file, 'li')->parentsUntil()->size());
    $this->assertEquals(2, qp($file, 'li')->parentsUntil('root')->size());
  }
  
  public function testCloneAll() {
    $file = DATA_FILE;
    
    // Shallow test
    $qp = qp($file, 'unary');
    $one = $qp->get(0);
    $two = $qp->cloneAll()->get(0);
    $this->assertTrue($one !== $two);
    $this->assertEquals('unary', $two->tagName);
    
    // Deep test: make sure children are also cloned.
    $qp = qp($file, 'inner');
    $one = $qp->find('li')->get(0);
    $two = $qp->find(':root inner')->cloneAll()->find('li')->get(0);
    $this->assertTrue($one !== $two);
    $this->assertEquals('li', $two->tagName);
  }
  
  public function testBranch() {
    $qp = qp(QueryPath::HTML_STUB);
    $branch = $qp->branch();
    $branch->find('title')->text('Title');
    $qp->find('body')->text('This is the body');
    
    $this->assertEquals($qp->top()->find('title')->text(), $branch->top()->find('title')->text());
  }
  
  public function testXpath() {
    $file = DATA_FILE;
    
    $this->assertEquals('head', qp($file)->xpath("//*[@id='head']")->tag());
  }
    
  public function test__clone() {
    $file = DATA_FILE;
    
    $qp = qp($file, 'inner:first');
    $qp2 = clone $qp;
    $this->assertFalse($qp === $qp2);
    $qp2->find('li')->attr('foo', 'bar');
    $this->assertEquals('', $qp->find('li')->attr('foo'));
    $this->assertEquals('bar', $qp2->attr('foo'));
  }
  
  public function testStub() {
    $this->assertEquals(1, qp(QueryPath::HTML_STUB)->find('title')->size());
  }
  
  public function testIterator() {
    
    $qp = qp(QueryPath::HTML_STUB, 'body')->append('<li/><li/><li/><li/>');
    $i = 0;
    foreach ($qp->find('li') as $li) {
      ++$i;
      $li->text('foo');
    }
    $this->assertEquals(4, $i);
    $this->assertEquals('foofoofoofoo', $qp->top()->find('li')->text());
  }
}