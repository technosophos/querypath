<?php
/**
 * This package contains classes for handling database transactions from
 * within QueryPath.
 *
 * The tools here use the PDO (PHP Data Objects) library to execute database
 * functions.
 * @package QueryPath
 * @subpackage Extension
 * @author M Butcher <matt@aleph-null.tv>
 * @license LGPL or MIT-like license.
 * @see QueryPathExtension
 * @see QueryPathExtensionRegistry::extend()
 */
 
/**
 * Provide DB access to a QueryPath object.
 *
 * This extension provides tools for communicating with a database using the 
 * QueryPath library. It relies upon PDO for underlying database communiction. This
 * means that it supports all databases that PDO supports, including MySQL, 
 * PostgreSQL, and SQLite.
 *
 * Here is an extended example taken from the unit tests for this library.
 * 
 * Let's say we create a database with code like this:
 * <code>
 *<?php
 * public function setUp() {
 *   $this->db = new PDO($this->dsn);
 *   $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 *   $this->db->exec('CREATE TABLE IF NOT EXISTS qpdb_test (colOne, colTwo, colThree)');
 *   
 *   $stmt = $this->db->prepare(
 *     'INSERT INTO qpdb_test (colOne, colTwo, colThree) VALUES (:one, :two, :three)'
 *   );
 *   
 *   for ($i = 0; $i < 5; ++$i) {
 *     $vals = array(':one' => 'Title ' . $i, ':two' => 'Body ' . $i, ':three' => 'Footer ' . $i);
 *     $stmt->execute($vals);
 *     $stmt->closeCursor();
 *   }
 * }
 * ?>
 * </code>
 * 
 * From QueryPath with QPDB, we can now do very elaborate DB chains like this:
 *<code>
 * <?php
 * $sql = 'SELECT * FROM qpdb_test';
 * $args = array();
 * $qp = qp(QueryPath::HTML_STUB, 'body') // Open a stub HTML doc and select <body/>
 *   ->append('<h1></h1>') // Add <h1/>
 *   ->children()  // Select the <h1/>
 *   ->dbInit($this->dsn) // Connect to the database
 *   ->query($sql, $args) // Execute the SQL query
 *   ->nextRow()  // Select a row. By default, no row is selected.
 *   ->appendColumn('colOne') // Append Row 1, Col 1 (Title 0)
 *   ->parent() // Go back to the <body/>
 *   ->append('<p/>') // Append a <p/> to the body
 *   ->find('p')  // Find the <p/> we just created.
 *   ->nextRow() // Advance to row 2
 *   ->prependColumn('colTwo') // Get row 2, col 2. (Body 1)
 *   ->columnAfter('colThree') // Get row 2 col 3. (Footer 1)
 *   ->doneWithQuery() // Let QueryPath clean up.
 *   ->writeHTML(); // Write the output as HTML.
 * ?> 
 *</code>
 * With the code above, we step through the document, selectively building elements
 * as we go, and then populating this elements with data from our initial query.
 *
 * When the last command, {@link QueryPath:::writeHTML()}, is run, we will get output
 * like this:
 * <code>
 *   <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
 *   <html xmlns="http://www.w3.org/1999/xhtml">
 *     <head>
 *     	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 *     	<title>Untitled</title>
 *     </head>
 *     <body>
 *       <h1>Title 0</h1>
 *       <p>Body 1</p>
 *       Footer 1</body>
 *    </html>
 * </code>
 * Notice the body section in particular. This is where the data has been
 * inserted.
 *
 * Sometimes you want to do something a lot simpler, like give QueryPath a 
 * template and have it navigate a query, inserting the data into a template, and
 * then inserting the template into the document. This can be done simply with 
 * the {@link queryInto()} function.
 *
 * Here's an example from another unit test:
 * <code>
 * <?php
 * $template = '<?xml version="1.0"?><li class="colOne"/>';
 * $sql = 'SELECT * FROM qpdb_test';
 * $args = array();
 * $qp = qp(QueryPath::HTML_STUB, 'body')
 *   ->append('<ul/>') // Add a new <ul/>
 *   ->children() // Select the <ul/>
 *   ->dbInit($this->dsn) // Initialize the DB
 *   // BIG LINE: Query the results, run them through the template, and insert them.
 *   ->queryInto($sql, $args, $template) 
 *   ->writeHTML(); // Write the results as HTML.
 * ?>
 * </code>
 * The simple code above puts the first column of the select statement
 * into an unordered list. The example output looks like this:
 * <code>
 * <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
 * <html xmlns="http://www.w3.org/1999/xhtml">
 *   <head>
 *   	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
 *   	<title>Untitled</title>
 *    </head>
 *    <body>
 *    <ul>
 *    <li class="colOne">Title 0</li>
 *    <li class="colOne">Title 1</li>
 *    <li class="colOne">Title 2</li>
 *    <li class="colOne">Title 3</li>
 *    <li class="colOne">Title 4</li>
 *    </ul>
 *   </body>
 * </html>
 * </code>
 */
class QPDB implements QueryPathExtension {
  protected $qp;
  protected $dsn;
  protected $db;
  protected $opts;
  protected $row = NULL;
  protected $stmt = NULL;
  
  /**
   * Used to control whether or not all rows in a result should be cycled through.
   */
  protected $cycleRows = FALSE;
  
  public function __construct(QueryPath $qp) {
    $this->qp = $qp;
  }
  
  /**
   * Create a new connection to the database. Use the PDO DSN syntax for a 
   * connection string.
   *
   * @param string $dsn
   *  The PDO DSN connection string.
   * @param array $options
   *  Connection options. The following options are supported:
   *  - username => (string)
   *  - password => (string)
   *  - db params => (array) These will be passed into the new PDO object.
   *    See the PDO documentation for a list of options. By default, the
   *    only flag set is {@link PDO::ATTR_ERRMODE}, which is set to 
   *    {@link PDO::ERRMODE_EXCEPTION}.
   * @return QueryPath
   *  The QueryPath object.
   * @throws PDOException
   *  The PDO library is configured to throw exceptions, so any of the 
   *  database functions may throw a PDOException.
   */
  public function dbInit($dsn, $options = array()) {
    $this->opts = $options + array(
      'username' => NULL,
      'password' => NULL,
      'db params' => array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
    );
    $this->dsn = $dsn;
    $this->db = new PDO($dsn);
    foreach ($this->opts['db params'] as $key => $val)
      $this->db->setAttribute($key, $val);
    
    return $this->qp;
  }
  
  /**
   * Execute a SQL query, and store the results.
   *
   * Use this when you need to access the results.
   * 
   * Example:
   * <code>
   * <?php
   * $args = array(':something' => 'myColumn');
   * qp()->QPDB($dsn)->query('SELECT :something FROM foo', $args);
   * ?>
   * </code>
   * 
   * @param string $sql
   *  The query to be executed.
   * @param array $args
   *  An associative array of substitutions to make.
   * @throws PDOException
   *  Throws an exception if the query cannot be executed.
   */
  public function query($sql, $args = array()) {
    $this->stmt = $this->db->prepare($sql);
    $this->stmt->execute($args);
    return $this->qp;
  }
  
  /**
   * Query and append the results.
   * Run a query and inject the results directly into the 
   * elements in the QueryPath object.
   */
  public function queryInto($sql, $args = array(), $template = NULL) {
    $stmt = $this->db->prepare($sql);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute($args);
    
    // If no template, put all values in together.
    if (empty($template)) {
      foreach ($stmt as $row) foreach ($row as $datum) $this->qp->append($datum);
    }
    // Otherwise, we run the results through a template, and then append.
    else {
      foreach ($stmt as $row) $this->qp->tpl($template, $row);
    }
    
    $stmt->closeCursor();
    return $this->qp;
  }
  
  public function doneWithQuery() {
    $this->stmt->closeCursor();
    $this->stmt = NULL;
    $this->row = NULL;
    $this->cycleRows = FALSE;
    return $this->qp;
  }
  
  /**
   * Execute a SQL query, but expect no value.
   * 
   * If your SQL query will have parameters, you are encouraged to
   * use {@link query()}, which includes built-in SQL Injection 
   * protection.
   *
   * @param string $sql
   *  A SQL statement.
   * @throws PDOException 
   *  An exception will be thrown if a query cannot be executed.
   */
  public function exec($sql) {
    $this->exec($sql);
    return $this->qp;
  }
  
  /**
   * Advance the query results row cursor.
   */
  public function nextRow() {
    $this->row = $this->stmt->fetch(PDO::FETCH_ASSOC);
    return $this->qp;
  }
  
  public function withEachRow() {
    $this->cycleRows = TRUE;
    return $this->qp;
  }
  
  protected function addData($columnName, $qpFunc = 'append') {
    $columns = is_array($columnName) ? $columnName : array($columnName);
    if ($this->cycleRows) {
      foreach ($this->stmt->fetch(PDO::FETCH_ASSOC) as $row) {
        $this->row = $row; // Keep internal pointer accurate.
        foreach ($columns as $col) {
          if (isset($row[$col])) $this->qp->$qpFunc($col);
        }
      }
      $this->cycleRows = FALSE;
      $this->closeCursor();
    }
    else {
      if ($this->row !== FALSE) {
        foreach ($columns as $col) {
          if (isset($this->row[$col])) $this->qp->$qpFunc($this->row[$col]);
        }
      }
    }
    return $this->qp;
  }
  
  public function getStatement() {
    return $this->stmt;
  }
  
  public function appendColumn($columnName) {return $this->addData($columnName, 'append'); }
  
  public function prependColumn($columnName) {return $this->addData($columnName, 'prepend');}
  
  public function columnBefore($columnName) {return $this->addData($columnName, 'before');}
  
  public function columnAfter($columnName) {return $this->addData($columnName, 'after');}
  
}

// The define allows another class to extend this.
if (!defined('QPDB_OVERRIDE'))
  QueryPathExtensionRegistry::extend('QPDB');