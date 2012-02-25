<?php
namespace QueryPath\Tests;

require_once 'PHPUnit/Autoload.php';
require_once __DIR__ . '/TestCase.php';
//require_once __DIR__ . '/../../../src/qp.php';
require_once __DIR__ . '/../../../src/QueryPath.php';

class QueryPath_Test extends TestCase {

  public function testWith() {
    $qp = \QueryPath::with(\QueryPath::XHTML_STUB);

    $this->assertInstanceOf('\QueryPath\QueryPath', $qp);

  }

  public function testWithHTML() {
    $qp = \QueryPath::with(\QueryPath::HTML_STUB);

    $this->assertInstanceOf('\QueryPath\QueryPath', $qp);
  }

  public function testWithXML() {
    $qp = \QueryPath::with(\QueryPath::XHTML_STUB);

    $this->assertInstanceOf('\QueryPath\QueryPath', $qp);
  }

}
