<?php
/**
 * Tests for the QueryPath library.
 * @package Tests
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
 */
class QueryPathExtensionTest extends QueryPathTest {
//class QueryPathExtensionTest extends PHPUnit_Framework_TestCase {
 public function testExtensions() {
   $this->assertNotNull(qp());
 }
 
 public function testHasExtension() {
   $this->assertTrue(\QueryPath\ExtensionRegistry::hasExtension('StubExtensionOne'));
 }
 
 public function testStubToe() {
   $this->assertEquals(1, qp(DATA_FILE, 'unary')->stubToe()->find(':root > toe')->size());
 }
 
 public function testStuble() {
   $this->assertEquals('arg1arg2', qp(DATA_FILE)->stuble('arg1', 'arg2'));
 }
 
 /**
  * @expectedException \QueryPath\QueryPathException
  */
 public function testNoRegistry() {
   \QueryPath\ExtensionRegistry::$useRegistry = FALSE;
   try {
    qp(DATA_FILE)->stuble('arg1', 'arg2'); 
   }
   catch (\QueryPath\QueryPathException $e) {
     \QueryPath\ExtensionRegistry::$useRegistry = TRUE;
     throw $e;
   }
   
 }
 
 public function testExtend() {
   $this->assertFalse(\QueryPath\ExtensionRegistry::hasExtension('StubExtensionThree'));
   \QueryPath\ExtensionRegistry::extend('StubExtensionThree');
   $this->assertTrue(\QueryPath\ExtensionRegistry::hasExtension('StubExtensionThree'));
 }
 
 /**
  * @expectedException \QueryPath\QueryPathException
  */
 public function testAutoloadExtensions() {
   // FIXME: This isn't really much of a test.
   \QueryPath\ExtensionRegistry::autoloadExtensions(FALSE);
   try {
    qp()->stubToe();
   }
   catch (Exception $e) {
     \QueryPath\ExtensionRegistry::autoloadExtensions(TRUE);
     throw $e;
   }
 }
 
 /**
  * @expectedException \QueryPath\QueryPathException
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
class StubExtensionOne implements \QueryPath\Extension {
  private $qp = NULL;
  public function __construct(\QueryPath\QueryPath $qp) {
    $this->qp = $qp;
  }
  
  public function stubToe() {
    $this->qp->find(':root')->append('<toe/>')->end();
    return $this->qp;
  }
}
class StubExtensionTwo implements \QueryPath\Extension {
  private $qp = NULL;
  public function __construct(\QueryPath\QueryPath $qp) {
    $this->qp = $qp;
  }
  public function stuble($arg1, $arg2) {
    return $arg1 . $arg2;
  }
}

class StubExtensionThree implements \QueryPath\Extension {
  private $qp = NULL;
  public function __construct(\QueryPath\QueryPath $qp) {
    $this->qp = $qp;
  }
  public function stuble($arg1, $arg2) {
    return $arg1 . $arg2;
  }
}

\QueryPath\ExtensionRegistry::extend('StubExtensionOne');
\QueryPath\ExtensionRegistry::extend('StubExtensionTwo');
