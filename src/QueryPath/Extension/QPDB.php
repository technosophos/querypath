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
 */
class QPDB implements QueryPathExtension {
  protected $qp;
  protected $dsn;
  protected $db;
  
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
   * @return QueryPath
   *  The QueryPath object.
   */
  public function QPDB($dsn, $options = array()) {
    $this->dsn = $dsn;
    $this->db = new PDO($dsn);
    
    return $this->qp;
  }
  
  public function query($sql, $args = array()) {
    $this->stmt = $this->db->prepare($sql);
    $this->stmt->execute($args);
  }
  
  public function closeCursor() {
    $this->stmt->closeCursor();
    return $this->qp;
  }
  
  public function exec($sql) {
    $this->statement = $this->exec($sql);
    return $this->qp;
  }
  
}

// The define allows another class to extend this.
if (!defined('QPDB_OVERRIDE'))
  QueryPathExtensionRegistry::extend('QPDB');