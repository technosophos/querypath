<?php
/** @file
 * This file contains the Query Path extension tools.
 *
 * Query Path can be extended to support additional features. To do this,
 * you need only create a new class that implements {@link QueryPathExtension}
 * and add your own methods. This class can then be registered as an extension.
 * It will then be available through Query Path.
 *
 * For information on building your own extension, see {@link QueryPathExtension}.
 * If you are trying to load an extension you have downloaded, chances are good that
 * all you need to do is {@link require_once} the file that contains the extension.
 *
 * @author M Butcher <matt@aleph-null.tv>
 * @license http://opensource.org/licenses/lgpl-2.1.php LGPL or MIT-like license.
 * @see QueryPathExtension
 * @see ExtensionRegistry::extend()
 */
namespace QueryPath;

/** @addtogroup querypath_extensions Extensions
 * The QueryPath extension system and bundled extensions.
 *
 * Much like jQuery, QueryPath provides a simple extension mechanism that allows
 * extensions to auto-register themselves upon being loaded. For a simple example, see
 * QPXML. For the internals, see QueryPathExntesion and QueryPath::__construct().
 */

/**
 * A QueryPathExtension is a tool that extends the capabilities of a QueryPath object.
 *
 * Extensions to QueryPath should implement the QueryPathExtension interface. The
 * only requirement is that the extension provide a constructor that takes a
 * QueryPath object as a parameter.
 *
 * Here is an example QueryPath extension:
 * <code><?php
 * class StubExtensionOne implements QueryPathExtension {
 *   private $qp = NULL;
 *   public function __construct(QueryPath $qp) {
 *     $this->qp = $qp;
 *   }
 *
 *   public function stubToe() {
 *     $this->qp->find(':root')->append('<toe/>')->end();
 *     return $this->qp;
 *   }
 * }
 * ExtensionRegistry::extend('StubExtensionOne');
 * ?></code>
 * In this example, the StubExtensionOne class implements QueryPathExtension.
 * The constructor stores a local copyof the QueryPath object. This is important
 * if you are planning on fully integrating with QueryPath's Fluent Interface.
 *
 * Finally, the stubToe() function illustrates how the extension makes use of
 * QueryPath internally, and remains part of the fluent interface by returning
 * the $qp object.
 *
 * Notice that beneath the class, there is a single call to register the
 * extension with QueryPath's registry. Your extension should end with a line
 * similar to this.
 *
 * <b>How is a QueryPath extension called?</b>
 *
 * QueryPath extensions are called like regular QueryPath functions. For
 * example, the extension above can be called like this:
 * <code>
 * qp('some.xml')->stubToe();
 * </code>
 * Since it returns the QueryPath ($qp) object, chaining is supported:
 * <code>
 * print qp('some.xml')->stubToe()->xml();
 * </code>
 * When you write your own extensions, anything that does not need to return a
 * specific value should return the QueryPath object. Between that and the
 * extension registry, this will provide the best developer experience.
 *
 * @ingroup querypath_extensions
 */
interface Extension {
  public function __construct(QueryPath $qp);
}
