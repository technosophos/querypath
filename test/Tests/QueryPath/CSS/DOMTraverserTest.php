<?php
/**
 * @file
 * CSS Event handling tests
 */
namespace QueryPath\Tests;

require_once __DIR__ . '/../TestCase.php';

use \QueryPath\CSS\Token;
use \QueryPath\CSS\DOMTraverser;
use \QueryPath\CSS\Parser;
use \QueryPath\CSS\EventHandler;

define('TRAVERSER_XML', __DIR__ . '/../../../DOMTraverserTest.xml');

/**
 * @ingroup querypath_tests
 * @group CSS
 */
class DOMTraverserTest extends TestCase {

  protected $xml_file = TRAVERSER_XML;
  public function debug($msg) {
    fwrite(STDOUT, PHP_EOL . $msg);
  }

  public function testConstructor() {
    $dom = new \DOMDocument('1.0');
    $dom->load($this->xml_file);

    $traverser = new DOMTraverser($dom);

    $this->assertInstanceOf('\QueryPath\CSS\Traverser', $traverser);
    $this->assertInstanceOf('\QueryPath\CSS\DOMTraverser', $traverser);
  }

  protected function traverser() {
    $dom = new \DOMDocument('1.0');
    $dom->load($this->xml_file);

    $traverser = new DOMTraverser($dom);

    return $traverser;
  }

  protected function find($selector) {
    return $this->traverser()->find($selector)->matches();
  }

  public function testFind() {
    $res = $this->traverser()->find('root');

    // Ensure that return contract is not violated.
    $this->assertInstanceOf('\QueryPath\CSS\Traverser', $res);
  }

  public function testMatches() {
    $res = $this->traverser()->matches();
    $this->assertEquals(1, count($res));
  }

  public function testMatchElement() {
    // Canary: If element does not exist, must return FALSE.
    $matches = $this->find('NO_SUCH_ELEMENT');
    $this->assertEquals(0, count($matches));

    // Test without namespace
    $matches = $this->find('root');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('crowded');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('outside');
    $this->assertEquals(3, count($matches));

    // Check nested elements.
    $matches = $this->find('a');
    $this->assertEquals(3, count($matches));

    // Test wildcard.
    $traverser = $this->traverser();
    $matches = $traverser->find('*')->matches();
    $actual= $traverser->getDocument()->getElementsByTagName('*');
    $this->assertEquals($actual->length, count($matches));


    // Test with namespace
    $this->markTestIncomplete();
  }

  public function testMatchAttributes() {

    $matches = $this->find('crowded[attr1]');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('crowded[attr1=one]');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('crowded[attr2^=tw]');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('classtest[class~=two]');
    $this->assertEquals(1, count($matches));
    $matches = $this->find('classtest[class~=one]');
    $this->assertEquals(1, count($matches));
    $matches = $this->find('classtest[class~=seven]');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('crowded[attr0]');
    $this->assertEquals(0, count($matches));

    $matches = $this->find('[level=1]');
    $this->assertEquals(3, count($matches));

    $matches = $this->find('[attr1]');
    $this->assertEquals(1, count($matches));

    // Test without namespace
    // Test with namespace
    $this->markTestIncomplete();
  }

  public function testMatchId() {
    $matches = $this->find('idtest#idtest-one');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('#idtest-one');
    $this->assertEquals(1, count($matches));

    $matches = $this->find('outter#fake');
    $this->assertEquals(0, count($matches));

    $matches = $this->find('#fake');
    $this->assertEquals(0, count($matches));
  }

  public function testMatchClasses() {
    // Basic test.
    $matches = $this->find('a.a1');
    $this->assertEquals(1, count($matches));

    // Count multiple.
    $matches = $this->find('.first');
    $this->assertEquals(2, count($matches));

    // Grab one in the middle of a list.
    $matches = $this->find('.four');
    $this->assertEquals(1, count($matches));

    // One element with two classes.
    $matches = $this->find('.three.four');
    $this->assertEquals(1, count($matches));
  }

  public function testMatchPseudoClasses() {
    $this->markTestIncomplete();
  }

  public function testMatchPseudoElements() {
    $this->markTestIncomplete();
  }

  public function testCombineAdjacent() {
    // Simple test
    $matches = $this->find('idtest + p');
    $this->assertEquals(1, count($matches));
    foreach ($matches as $m) {
      $this->assertEquals('p', $m->tagName);
    }

    // Test ignoring PCDATA
    $matches = $this->find('p + one');
    $this->assertEquals(1, count($matches));
    foreach ($matches as $m) {
      $this->assertEquals('one', $m->tagName);
    }

    // Test that non-adjacent elements don't match.
    $matches = $this->find('idtest + one');
    foreach ($matches as $m) {
      $this->assertEquals('one', $m->tagName);
    }
    $this->assertEquals(0, count($matches), 'Non-adjacents should not match.');

    // Test that elements BEFORE don't match
    $matches = $this->find('one + p');
    foreach ($matches as $m) {
      $this->assertEquals('one', $m->tagName);
    }
    $this->assertEquals(0, count($matches), 'Match only if b is after a');

    $this->markTestIncomplete();
  }
  public function testCombineSibling() {
    $this->markTestIncomplete();
  }
  public function testCombineDirectDescendant() {
    $this->markTestIncomplete();
  }
  public function testCombineAnyDescendant() {
    $this->markTestIncomplete();
  }

}

