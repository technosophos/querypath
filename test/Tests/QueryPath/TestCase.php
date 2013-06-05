<?php
/**
 * @file
 *
 * The master test case.
 */

namespace QueryPath\Tests;

$basedir = dirname(dirname(dirname(__DIR__))); // ../../../

require_once 'PHPUnit/Autoload.php';
require_once $basedir . '/vendor/autoload.php';
require_once $basedir . '/src/qp.php';

class TestCase extends \PHPUnit_Framework_TestCase {
  const DATA_FILE =  'test/data.xml';
  public static function setUpBeforeClass(){
  }

  public function testFoo() {
    // Placeholder. Why is PHPUnit emitting warnings about no tests?
  }
}
