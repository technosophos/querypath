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
  * Download from the [GitHub Tags page](https://github.com/technosophos/querypath/tags).
  * [Composer](http://packagist.org): Add this to the 'require' section of your `composer.json`:

```php
{
  "require": {
    "querypath/QueryPath": ">=2.0.0"
  }
}
```

Or if you prefer PEAR:
```
$ pear channel-discover pear.querypath.org
$ pear install querypath/QueryPath
```

### Older releases

We used to maintain phar and minimal packages, but due to the low usage
rate, and the fact that PHP is changing, we will no longer be
providing these.

Older releases are still available here:

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

## Including QueryPath

If you installed QueryPath as a PEAR package, use it like this:

```php
<?php
require 'QueryPath/QueryPath.php';
?>
```

Unfortunately, in the 2.1 branch of QueryPath, the Composer include is:

```php
<?php
require 'vendor/querypath/QueryPath/src/QueryPath/QueryPath.php';
?>
```

The next major release of QueryPath will support Composer autoloading.

With the Phar archive, you can include QueryPath like this:

```php
<?php
require 'QueryPath.phar';
?>
```


From there, the main functions you will want to use are `qp()` and `htmlqp()`. Start with the [API docs](http://api.querypath.org/docs).
