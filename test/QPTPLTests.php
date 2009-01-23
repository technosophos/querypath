<?php
/**
 * Tests for the QueryPath library.
 * @package QueryPath
 * @subpackage Tests
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once '../src/QueryPath/QueryPath.php';
require_once '../src/QueryPath/Extension/QPTPL.php';

class QPTPLTests extends PHPUnit_Framework_TestCase {
  
  public function testTplArray() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><item class="myclass"/><item id="one"/></data>';
    $data = array('.myclass' => 'VALUE', '#one' => '<b>OTHER VALUE</b>');
    $qp = qp($xml, 'root')->tpl($tpl, $data)->writeHTML();
    $this->assertEquals('VALUE', $qp->find(':root .myclass')->text());
    $this->assertEquals(1, $qp->find(':root b')->size());
  }
  
  public function testTplObject() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><item class="MyClass"/><item id="one"/></data>';
    $o = new IntrospectMe();
    $qp = qp($xml)->tpl($tpl, $o);
    $this->assertEquals('FOO', $qp->find('.MyClass')->text());
    
    $tpl = '<?xml version="1.0"?><data><item class="MyClass"/><item class="OtherData"/></data>';
    $o = new FixtureTwo();
    $qp = qp($xml)->tpl($tpl, $o);
    $this->assertEquals(2, $qp->find('.MyClass br')->size());
    $this->assertEquals('This is a message', $qp->find(':root .OtherData')->text());
    
  }
  
  public function testTplAll() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><first class="Baz"/><item class="MyClass"/><item class="otherData"/></data>';
    $objects = array(new IntrospectMe(), new FixtureOne(), new FixtureTwo()); 
    $qp = qp($xml)->tplAll($tpl, $objects);
    
    $this->assertEquals('FOOFOO', $qp->find('.MyClass')->text());
    $this->assertEquals(1, $qp->find(':root #baz')->size());
    $this->assertEquals(1, $qp->find('#baz')->size());
  }
  
  public function testTplMultiClass() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><item class="classb myclass classc"/><item id="one"/></data>';
    $data = array('.myclass' => 'VALUE', '#one' => '<b>OTHER VALUE</b>');
    $qp = qp($xml, 'root')->tpl($tpl, $data);
    $this->assertEquals('VALUE', $qp->find(':root .myclass')->text());
    $this->assertEquals(1, $qp->find(':root b')->size());
  }
  
  
}

class IntrospectMe {
  public function getMyClass() {
    return 'FOO';
  }
}

class FixtureOne {
  public function getBaz() {
    return '<str id="baz">This is a string</str>';
  }
}

class FixtureTwo {
  private $db;
  
  public function __construct() {
    $this->db = new PDO('sqlite:./db/qpTest.db');
    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $this->db->exec('CREATE TABLE IF NOT EXISTS test (message TEXT)');
    $this->db->exec('INSERT INTO test (message) VALUES ("This is a message")');
  }
  
  public function __destruct() {
    $this->db->exec('DROP TABLE test');
  }
  
  public function getMyClass() {
    return '<br/>FOO<br/>';
  }
  
  public function getOtherData() {
    $stmt = $this->db->prepare('SELECT message FROM test');
    $stmt->execute();
    
    $o = $stmt->fetchObject();
    $msg = $o->message;
    $stmt->closeCursor();
    return $msg;
  }
}