<?php
namespace QueryPath;
interface Query {
  public function __construct($document, $selector, $options);
  /*
  public function find($selector);
  public function count();
   */
}
