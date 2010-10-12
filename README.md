# QueryPath: Find your way.

Authors: Matt Butcher (lead), Emily Brand, and others

[Website](http://querypath.org) | 
[API Docs](http://api.querypath.org) |
[VCS and Issue Tracking](http://github.com/technosophos/querypath) |
[Support List](http://groups.google.com/group/support-querypath) |
[Developer List](http://groups.google.com/group/devel-querypath) |
[Pear channel](http://pear.querypath.org) |

This package is licensed under the GNU LGPL 2.1 (COPYING-LGPL.txt) or, at your choice, an MIT-style
license (COPYING-MIT.txt). The licenses should have been distributed with this library.

## Installing QueryPath

The following packages of QueryPath are available:

  * PEAR package (`pear install querypath/QueryPath`): Installs the library and documentation.
  * Phar (QueryPath-VERSION.phar): This is a Phar package which can be used as-is. Its size has been
    minimized by stripping comments. It is designed for direct inclusion in PHP 5.3 applications.
  * Minimal (QueryPath-VERSION-minimal.tgz): This contains *only* the QueryPath library, with no
    documentation or additional build environment. It is designed for production systems.
  * Full (QueryPath-VERSION.tgz): This contains QueryPath, its unit tests, its documentation, 
    examples, and all supporting material. If you are starting with QueryPath, this might be the
    best package.
  * Docs (QueryPath-VERSION-docs.tgz): This package contains *only* the documentation for QueryPath.
    Generally, this is useful to install as a complement to the minimal package.
  * Git repository clone: You can always clone [this repository](http://github.com/technosophos/querypath) and work from that code.

    
If in doubt, you probably want the PEAR version or the [Full package](http://github.com/technosophos/querypath/downloads).

    $ pear channel-discover pear.querypath.org
    $ pear install querypath/QueryPath

To use QueryPath as a **standard PHP library**, simply put it somewhere PHP can see it and include `QueryPath/QueryPath.php` (that's in `src/` in the full distro).

To use QueryPath as a **phar package**, put the phar somewhere where PHP can see it and include `QueryPath.phar`.

To use QueryPath as a PEAR package (assuming to have already installed it with PEAR), include `QueryPath/QueryPath.php`.

From there, the main functions you will want to use are `qp()` and `htmlqp()`. Start with the [API docs](http://api.querypath.org/docs).