<?php
/**
 * Tests for the QueryPath library.
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once 'src/QueryPath/QueryPath.php';
require_once 'src/QueryPath/Extension/QPXML.php';
/**
 * @ingroup querypath_tests
 */
class QPXMLTests extends PHPUnit_Framework_TestCase {
  
  protected $file = './test/advanced.xml';
  
  public function testCDATA() {
    $this->assertEquals('This is a CDATA section.', qp($this->file, 'first')->cdata());
    
    $msg = 'Another CDATA Section';
    $this->assertEquals($msg, qp($this->file, 'second')->cdata($msg)->top()->find('second')->cdata());
  }
  
  public function testComment(){
    $this->assertEquals('This is a comment.', trim(qp($this->file, 'root')->comment()));
    $msg = "Message";
    $this->assertEquals($msg, qp($this->file, 'second')->comment($msg)->top()->find('second')->comment());
  }
  
  public function testProcessingInstruction() {
    $this->assertEquals('This is a processing instruction.', trim(qp($this->file, 'third')->pi()));
    $msg = "Message";
    $this->assertEquals($msg, qp($this->file, 'second')->pi('qp', $msg)->top()->find('second')->pi());
  }
}