<?php
/**
 * Tests for the QueryPath library.
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once 'src/QueryPath/QueryPath.php';
require_once 'src/QueryPath/Extension/QPTPL.php';
/**
 * @ingroup querypath_tests
 */
class QPTPLTest extends PHPUnit_Framework_TestCase {
  
  public function testIsAssoc() {
    $t = new QPTPL(qp());
    $this->assertTrue($t->isAssoc(array('one' => '0', 'two' => '1')));
    $this->assertTrue($t->isAssoc(array('0' => '0', 'two' => '1')));
    $this->assertFalse($t->isAssoc(array(0,1,2)));
    // Test manual key assignment:
    $this->assertFalse($t->isAssoc(array(0 => 1, 1 => 2, 2 => 3)));
    // Index not in order:
    $this->assertTrue($t->isAssoc(array(0 => 0,3 => 1,2 => 2)));
  }
  
  public function testTplArray() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><item class="myclass"/><item id="one"/></data>';
    $data = array('.myclass' => 'VALUE', '#one' => '<b>OTHER VALUE</b>');
    $qp = qp($xml, 'root')->tpl($tpl, $data);
    $this->assertEquals('VALUE', $qp->find(':root .myclass')->text());
    $this->assertEquals(1, $qp->find(':root b')->size());
  }
  
  public function testTplUnmarkedArray() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><item class="myclass"/><item class="other"/></data>';
    $data = array('myclass' => 'VALUE', 'other' => '<b>OTHER VALUE</b>');
    $qp = qp($xml, 'root')->tpl($tpl, $data);
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
    $this->assertTrue($qp->is('#baz'));
  }
  
  public function testTplMultiClass() {
    $xml = '<?xml version="1.0"?><root/>';
    $tpl = '<?xml version="1.0"?><data><item class="classb myclass classc"/><item id="one"/></data>';
    $data = array('.myclass' => 'VALUE', '#one' => '<b>OTHER VALUE</b>');
    $qp = qp($xml, 'root')->tpl($tpl, $data);
    $this->assertEquals('VALUE', $qp->find(':root .myclass')->text());
    $this->assertEquals(1, $qp->find(':root b')->size());
  }
  
  public function testTplRecursion() {

    $tpl = '<?xml version="1.0"?><table>
    <tbody>
      <tr class="header-row">
        <th class="header1"/>
        <th class="header2"/>
      </tr>
      <tr class="table-row">
        <td class="cell1"></td>
        <td class="cell2"></td>
      </tr>
    </tbody>
    </table>';
    
    $data['.header1'][] = 'Header One';
    $data['.header2'][] = 'Header Two';
    $data['.table-row'][] = array(
      '.cell1' => 'Cell One',
      '.cell2' => 'Cell Two',
    );
    $data['.table-row'][] = array(
      '.cell1' => 'Cell Three',
      '.cell2' => 'Cell Four',
    );
    $data['.table-row'][] = array(
      '.cell1' => 'Cell Five',
      '.cell2' => 'Cell Six',
    );
    $qp = qp(QueryPath::HTML_STUB, 'body')->tpl($tpl, $data);
    $this->assertEquals('Cell Six', $qp->top()->find('.table-row:last .cell2')->text());
    $this->assertEquals(6, $qp->top()->find('td')->size());
    
    // Test with class substitution for multiple items
    // and same class.
    $tpl = '<?xml version="1.0"?>
    <div>
    <ul class="list">
      <li class="item"/>
    </ul>
    </div>';
    
    $data = array();
    $data['.item'][] = 'One';
    $data['.item'][] = 'Two';
    $data['.item'][] = 'Three';
    $data['.item'][] = 'Four';
    
    $qp = qp(QueryPath::HTML_STUB, 'body')->tpl($tpl, $data);
    $this->assertEquals(4, $qp->top('.item')->size());
    
    // Same test as before, but with one item set to NULL.
    $data = array();
    $data['.item'][] = 'One';
    $data['.item'][] = 'Two';
    $data['.item'][] = NULL;
    $data['.item'][] = 'Four';
    
    $qp = qp(QueryPath::HTML_STUB, 'body')->tpl($tpl, $data);
    $this->assertEquals(4, $qp->top('.item')->size());
    $this->assertEquals('One', $qp->eq(0)->text());
    $this->assertEquals('', $qp->eq(2)->text());
    
  }
  
  public function testTplTraversable() {
    // Test that a Traversable will work.
    $tpl = '<?xml version="1.0"?><data><item class="classb myclass classc"/><item id="one"/></data>';
    $data = new ArrayIterator(array('.myclass' => 'VALUE', '#one' => '<b>OTHER VALUE</b>'));
    $qp = qp(QueryPath::HTML_STUB, 'body')->tpl($tpl, $data);
    $this->assertEquals('VALUE', $qp->top()->find('.myclass')->text());
  }
  
}
/**
 * @ingroup querypath_tests
 */
class IntrospectMe {
  public function getMyClass() {
    return 'FOO';
  }
}
/**
 * @ingroup querypath_tests
 */
class FixtureOne {
  public function getBaz() {
    return '<str id="baz">This is a string</str>';
  }
}
/**
 * @ingroup querypath_tests
 */
class FixtureTwo {
  private $db;
  
  public function __construct() {
    $this->db = new PDO('sqlite:./test/db/qpTest2.db');
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