# QueryPath: Find your way.

Authors: Matt Butcher (lead), Emily Brand, and many others

[Website](http://querypath.org) |
[API Docs](http://api.querypath.org/docs) |
[VCS and Issue Tracking](http://github.com/technosophos/querypath) |
[Support List](http://groups.google.com/group/support-querypath) |
[Developer List](http://groups.google.com/group/devel-querypath) |
[Pear channel](http://pear.querypath.org) |

This package is licensed under an MIT license (COPYING-MIT.txt).

## At A Glance

QueryPath is a jQuery-like library for working with XML and HTML
documents in PHP. It now contains support for HTML5 via the
[HTML5-PHP project](https://github.com/Masterminds/html5-php).

### Gettings Started

Assuming you have successfully installed QueryPath via Composer, you can
parse documents like this:

```
require_once "vendor/autoload.php";

// HTML5 (new)
$qp = html5qp("path/to/file.html");

// Legacy HTML via libxml
$qp = htmlqp("path/to/file.html");

// XML or XHTML
$qp = qp("path/to/file.html");

// All of the above can take string markup instead of a file name:
$qp = qp("<?xml version='1.0'?><hello><world/></hello>")

```

But the real power comes from chaining. Check out the example below.

### Example Usage

Say we have a document like this:
```xml
<?xml version="1.0"?>
<table>
  <tr id="row1">
    <td>one</td><td>two</td><td>three</td>
  </tr>
  <tr id="row2">
    <td>four</td><td>five</td><td>six</td>
  </tr>
</table>
```

And say that the above is stored in the variable `$xml`. Now
we can use QueryPath like this:

```php
<?php
// Add the attribute "foo=bar" to every "td" element.
qp($xml, 'td')->attr('foo', 'bar');

// Print the contents of the third TD in the second row:
print qp($xml, '#row2>td:nth(3)')->text();

// Append another row to the XML and then write the
// result to standard output:
qp($xml, 'tr:last')->after('<tr><td/><td/><td/></tr>')->writeXML();

?>
```

(This example is in `examples/at-a-glance.php`.)

With over 60 functions and robust support for chaining, you can
accomplish sophisticated XML and HTML processing using QueryPath.

## QueryPath Installers

The preferred method of installing QueryPath is via [Composer](http://getcomposer.org).

You can also download the package from GitHub.

### Composer (Preferred)

To add QueryPath as a library in your project, add this to the 'require'
section of your `composer.json`:

```json
{
  "require": {
    "querypath/QueryPath": ">=3.0.0"
  }
}
```

The run `php composer.phar install` in that directory.

To stay up to date on stable code, you can use `dev-master` instead of `>=3.0.0`.

### Manual Install

You can either download a stable release from the
[GitHub Tags page](https://github.com/technosophos/querypath/tags)
or you can use `git` to clone
[this repository](http://github.com/technosophos/querypath) and work from
the code.

## Including QueryPath

As of QueryPath 3.x, QueryPath uses the Composer autoloader if you
installed with composer:
```php
<?php
require 'vendor/autoload.php';
?>
```

Without Composer, you can include QueryPath like this:

```php
<?php
require 'QueryPath/src/qp.php';
?>
```

QueryPath can also be compiled into a Phar and then included like this:

```php
<?php
require 'QueryPath.phar';
?>
```

From there, the main functions you will want to use are `qp()`
(alias of `QueryPath::with()`) and `htmlqp()` (alias of
`QueryPath::withHTML()`). Start with the
[API docs](http://api.querypath.org/docs).
