<?php
namespace QueryPath\Tests;
require_once __DIR__ . '/TestCase.php';

class SelectorTest extends TestCase {

  protected function parse($selector) {
    $handler = new \QueryPath\CSS\Selector();
    $parser = new \QueryPath\CSS\Parser($selector, $handler);
    $parser->parse();
    return $handler;
  }

  public function testElement() {
    $selector = $this->parse('test')->toArray();

    $this->assertEquals(1, count($selector));
    $this->assertEquals('test', $selector['0']->element);
  }

  public function testElementNS() {
    $selector = $this->parse('foo|test')->toArray();

    $this->assertEquals(1, count($selector));
    $this->assertEquals('test', $selector['0']->element);
    $this->assertEquals('foo', $selector['0']->ns);
  }

  public function testId() {
    $selector = $this->parse('#test')->toArray();

    $this->assertEquals(1, count($selector));
    $this->assertEquals('test', $selector[0]->id);
  }

  public function testClasses() {
    $selector = $this->parse('.test')->toArray();

    $this->assertEquals(1, count($selector));
    $this->assertEquals('test', $selector[0]->classes[0]);

    $selector = $this->parse('.test.foo.bar')->toArray();
    $this->assertEquals('test', $selector[0]->classes[0]);
    $this->assertEquals('foo', $selector[0]->classes[1]);
    $this->assertEquals('bar', $selector[0]->classes[2]);

  }

  public function testAttributes() {
  }

  public function testAttributesNS() {
  }

  public function testPseudoClasses() {
  }

  public function testPseudoElements() {
  }

  public function testCombinators() {
  }

  public function testIterator() {
  }
}
