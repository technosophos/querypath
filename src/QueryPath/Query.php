<?php
namespace QueryPath;
interface Query {
  public function __construct($document, $selector, $options);
  public function find($selector);
  public function top($selector);
  public function next($selector);
  public function prev($selector);
  public function siblings($selector);
  public function parent($selector);
  public function children($selector);
}
