<?php
/**
 * Tests for the QueryPath library.
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once 'src/QueryPath/QueryPath.php';

/**
 * @ingroup querypath_tests
 */
class QueryPathOptionsTest extends PHPUnit_Framework_TestCase {
  
  public function testQueryPathOptions() {
    $expect = array('test1' => 'val1', 'test2' => 'val2');
    $options = array('test1' => 'val1', 'test2' => 'val2');
    
    QueryPathOptions::set($options);
    
    $results = QueryPathOptions::get();
    $this->assertEquals($expect, $results);
    
    $this->assertEquals('val1', $results['test1']);
  }
  
  public function testQPOverrideOrder() {
    $expect = array('test1' => 'val3', 'test2' => 'val2');
    $options = array('test1' => 'val1', 'test2' => 'val2');
    
    QueryPathOptions::set($options);
    $qpOpts = qp(NULL, NULL, array('test1'=>'val3', 'replace_entities' => TRUE))->getOptions();
    
    $this->assertEquals($expect['test1'], $qpOpts['test1']);
    $this->assertEquals(TRUE, $qpOpts['replace_entities']);
    $this->assertNull($qpOpts['parser_flags']);
    $this->assertEquals($expect['test2'], $qpOpts['test2']);
  }
  
  public function testQPHas() {
    $options = array('test1' => 'val1', 'test2' => 'val2');
    
    QueryPathOptions::set($options);
    $this->assertTrue(QueryPathOptions::has('test1'));
    $this->assertFalse(QueryPathOptions::has('test3'));
  }
  public function testQPMerge() {
    $options = array('test1' => 'val1', 'test2' => 'val2');
    $options2 = array('test1' => 'val3', 'test4' => 'val4');
    
    QueryPathOptions::set($options);
    QueryPathOptions::merge($options2);
    
    $results = QueryPathOptions::get();
    $this->assertTrue(QueryPathOptions::has('test4'));
    $this->assertEquals('val3', $results['test1']);
  }
  
}