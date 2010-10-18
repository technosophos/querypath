<?php
/**
 * Tests for the QueryPath library.
 * @author M Butcher <matt@aleph-null.tv>
 * @license The GNU Lesser GPL (LGPL) or an MIT-like license.
 */

require_once 'PHPUnit/Framework.php';
require_once 'src/QueryPath/QueryPath.php';
require_once 'src/QueryPath/Extension/QPDB.php';
require_once 'src/QueryPath/Extension/QPTPL.php';

QPDB::baseDB('sqlite:./test/db/qpTest.db');

/**
 * @ingroup querypath_tests
 */
class QPDBTest extends PHPUnit_Framework_TestCase {
  private $dsn = 'sqlite:./test/db/qpTest.db';
  
  public function setUp() {
    $this->db = QPDB::getBaseDB();
    $this->db->exec('CREATE TABLE IF NOT EXISTS qpdb_test (colOne, colTwo, colThree)');
    
    $stmt = $this->db->prepare(
      'INSERT INTO qpdb_test (colOne, colTwo, colThree) VALUES (:one, :two, :three)'
    );
    
    for ($i = 0; $i < 5; ++$i) {
      $vals = array(':one' => 'Title ' . $i, ':two' => 'Body ' . $i, ':three' => 'Footer ' . $i);
      $stmt->execute($vals);
      $stmt->closeCursor();
    }
  }
  
  public function tearDown() {
    $this->db->exec('DROP TABLE qpdb_test');
    //$s = $this->db->prepare('DELETE FROM qpdb_test');
    //$s->execute();
    //$s->closeCursor();
  }
  
  public function testQueryInto() {
    // This is the only query that uses dbInit().
    $sql = 'SELECT "Hello", "World"';
    $qp = qp(QueryPath::HTML_STUB, 'body')->dbInit($this->dsn)->queryInto($sql)->doneWithQuery();
    $this->assertEquals('HelloWorld', $qp->top()->find('body')->text());
    
    $template = '<?xml version="1.0"?><li class="colOne"/>';
    $sql = 'SELECT * FROM qpdb_test';
    $args = array();
    $qp = qp(QueryPath::HTML_STUB, 'body')->append('<ul/>')->children()->queryInto($sql, $args, $template)->doneWithQuery();
    //$qp->writeHTML();
    $this->assertEquals(5, $qp->top()->find('li')->size());
    
    $template = '<?xml version="1.0"?><tr><td class="colOne"/><td class="colTwo"/><td class="colThree"/></tr>';
    $sql = 'SELECT * FROM qpdb_test';
    $args = array();
    $qp = qp(QueryPath::HTML_STUB, 'body')
      ->append('<table><tbody><tr><th>Title</th><th>Body</th><th>Foot</th></tr></tbody></table>')
      ->find('tbody')
      ->queryInto($sql, $args, $template)
      ->doneWithQuery()
      ;//->writeHTML();
    $this->assertEquals('Footer 4', $qp->top()->find('td:last')->text());
  }
  
  /*
  public function xtestExec() {
    $sql = 'INSERT INTO qpdb_test (colOne, colTwo, colThree) VALUES ("o", "t", "tr")';
    $qp = qp()->exec($sql)->doneWithQuery();
    $this->assertEquals(6, $qp->query('SELECT count(*) as c FROM qpdb_test')->getStatement()->fetchObject()->c);
    $qp->doneWithQuery();
  }
  */
  
  public function testQueryChains() {
    $sql = 'SELECT * FROM qpdb_test';
    $args = array();
    $qp = qp(QueryPath::HTML_STUB, 'body') // Open a stub HTML doc and select <body/>
      ->append('<h1></h1>') // Add <h1/>
      ->children()  // Select the <h1/>
      //->dbInit($this->dsn) // Connect to the database
      ->query($sql, $args) // Execute the SQL query
      ->nextRow()  // Select a row. By default, no row is selected.
      //->withEachRow()
      ->appendColumn('colOne') // Append Row 1, Col 1 (Title 0)
      ->parent() // Go back to the <body/>
      ->append('<p/>') // Append a <p/> to the body
      ->find('p')  // Find the <p/> we just created.
      ->nextRow() // Advance to row 2
      ->prependColumn('colTwo') // Get row 2, col 2. (Body 1)
      ->columnAfter('colThree') // Get row 2 col 3. (Footer 1)
      ->doneWithQuery() // Let QueryPath clean up.
      ;//->writeHTML(); // Write the output as HTML.
    $this->assertEquals('Title 0', $qp->top()->find('h1')->text());
    $this->assertEquals('Body 1', $qp->top()->find('p')->text());
    
    $qp = qp(QueryPath::HTML_STUB, 'body') // Open a stub HTML doc and select <body/>
      ->append('<table><tbody/></table>')
      ->find('tbody')
      ->query('SELECT * FROM qpdb_test LIMIT 2')
      ->withEachRow()
      ->appendColumn('colOne')
      ->doneWithQuery();
    $this->assertEquals('Title 0Title 1', $qp->top()->find('tbody')->text());
    
    $wrap = '<tr><td/></tr>';    
    $qp = qp(QueryPath::HTML_STUB, 'body')
      ->append('<table><tbody/></table>')
      ->find('tbody')
      ->query('SELECT * FROM qpdb_test LIMIT 2')
      ->withEachRow()
      ->appendColumn('colOne', $wrap)
      ->doneWithQuery()
      ;//->writeHTML();
    $this->assertEquals('Title 0', $qp->top()->find('td:first')->text());
  }
}