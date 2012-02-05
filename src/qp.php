<?php
/**
 * @file
 *
 * QueryPath functions.
 *
 * This file holds the QueryPath functions, qp() and htmlqp(). It also
 * statically includes the QueryPath library (without relying upon an
 * autoloader).
 *
 * Usage:
 *
 * @code
 * <?php
 * require 'qp.php';
 *
 * qp($xml)->find('foo')->count();
 * ?>
 * @endcode
 *
 * While using this library is not required, it is the only way to
 * access the qp()/htmlqp() functions which, since they are not classed,
 * cannot be loaded by an autoloader.
 */
