<?php
/**
 * Tests for the QueryPath library.
 * @package Tests
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
 
require_once 'PHPUnit/Framework.php';
require_once '../src/QueryPath/QueryPath.php';
require_once 'QueryPathTest.php';

/**
 * Run all of the usual tests, plus some extras, with some extensions loaded.
 */
class QueryPathExtensionTest extends QueryPathTest {
//class QueryPathExtensionTest extends PHPUnit_Framework_TestCase {
 public function testExtensions() {
   $this->assertNotNull(qp());
 }
 
 public function testStubToe() {
   $this->assertEquals(1, qp('./data.xml', 'unary')->stubToe()->find(':root > toe')->size());
 }
 
 public function testStuble() {
   $this->assertEquals('arg1arg2', qp('./data.xml')->stuble('arg1', 'arg2'));
 }
 
 /**
  * @expectedException QueryPathException
  */
 public function testNoRegistry() {
   QueryPathExtensionRegistry::$useRegistry = FALSE;
   try {
    qp('./data.xml')->stuble('arg1', 'arg2'); 
   }
   catch (QueryPathException $e) {
     QueryPathExtensionRegistry::$useRegistry = TRUE;
     throw $e;
   }
   
 }
 
 /**
  * @expectedException QueryPathException
  */
 public function testCallFailure() {
   qp()->foo();
 }
 
}
// Create a stub extension:
class StubExtensionOne implements QueryPathExtension {
  private $qp = NULL;
  public function __construct(QueryPath $qp) {
    $this->qp = $qp;
  }
  
  public function stubToe() {
    $this->qp->find(':root')->append('<toe/>')->end();
    return $this->qp;
  }
}
class StubExtensionTwo implements QueryPathExtension {
  private $qp = NULL;
  public function __construct(QueryPath $qp) {
    $this->qp = $qp;
  }
  public function stuble($arg1, $arg2) {
    return $arg1 . $arg2;
  }
}

QueryPathExtensionRegistry::extend('StubExtensionOne');
QueryPathExtensionRegistry::extend('StubExtensionTwo');
