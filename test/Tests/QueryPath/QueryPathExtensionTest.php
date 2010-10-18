<?php
/**
 * Tests for the QueryPath library.
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
 
require_once 'PHPUnit/Framework.php';
require_once 'src/QueryPath/QueryPath.php';
require_once 'QueryPathTest.php';

/**
 * 
 */
//define('DATA_FILE', 'test/data.xml');

/**
 * Run all of the usual tests, plus some extras, with some extensions loaded.
 * @ingroup querypath_tests
 */
class QueryPathExtensionTest extends QueryPathTest {
//class QueryPathExtensionTest extends PHPUnit_Framework_TestCase {
 public function testExtensions() {
   $this->assertNotNull(qp());
 }
 
 public function testHasExtension() {
   $this->assertTrue(QueryPathExtensionRegistry::hasExtension('StubExtensionOne'));
 }
 
 public function testStubToe() {
   $this->assertEquals(1, qp(DATA_FILE, 'unary')->stubToe()->find(':root > toe')->size());
 }
 
 public function testStuble() {
   $this->assertEquals('arg1arg2', qp(DATA_FILE)->stuble('arg1', 'arg2'));
 }
 
 /**
  * @expectedException QueryPathException
  */
 public function testNoRegistry() {
   QueryPathExtensionRegistry::$useRegistry = FALSE;
   try {
    qp(DATA_FILE)->stuble('arg1', 'arg2'); 
   }
   catch (QueryPathException $e) {
     QueryPathExtensionRegistry::$useRegistry = TRUE;
     throw $e;
   }
   
 }
 
 public function testExtend() {
   $this->assertFalse(QueryPathExtensionRegistry::hasExtension('StubExtensionThree'));
   QueryPathExtensionRegistry::extend('StubExtensionThree');
   $this->assertTrue(QueryPathExtensionRegistry::hasExtension('StubExtensionThree'));
 }
 
 /**
  * @expectedException QueryPathException
  */
 public function testAutoloadExtensions() {
   // FIXME: This isn't really much of a test.
   QueryPathExtensionRegistry::autoloadExtensions(FALSE);
   try {
    qp()->stubToe();
   }
   catch (Exception $e) {
     QueryPathExtensionRegistry::autoloadExtensions(TRUE);
     throw $e;
   }
 }
 
 /**
  * @expectedException QueryPathException
  */
 public function testCallFailure() {
   qp()->foo();
 }
 
 // This does not (and will not) throw an exception.
 // /**
 //   * @expectedException QueryPathException
 //   */
 //  public function testExtendNoSuchClass() {
 //    QueryPathExtensionRegistry::extend('StubExtensionFour');
 //  }
 
}
// Create a stub extension:
/**
 * Create a stub extension
 *
 * @ingroup querypath_tests
 */
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
/**
 * Create a stub extension
 *
 * @ingroup querypath_tests
 */
class StubExtensionTwo implements QueryPathExtension {
  private $qp = NULL;
  public function __construct(QueryPath $qp) {
    $this->qp = $qp;
  }
  public function stuble($arg1, $arg2) {
    return $arg1 . $arg2;
  }
}
/**
 * Create a stub extension
 *
 * @ingroup querypath_tests
 */
class StubExtensionThree implements QueryPathExtension {
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
