<?php
/**
 * Parsing PHP with QueryPath
 *
 * This file contains an example of how QueryPath can be used
 * to parse a PHP file. Any well-formed XML or HTML document can be parsed. Since
 * PHP tags are contained inside of processor instructions, an XML parser can 
 * correctly parse such a file into a DOM. Consequently, you can use QueryPath
 * to read, modify, and traverse PHP files.
 *
 * This example illustrates how such a file can be parsed and manipulated.
 *
 * @package Examples
 * @author M Butcher <matt@aleph-null.tv>
 * @license LGPL The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
?>
<html>
<head>
  <title>Parse PHP from QueryPath</title>
</head>
<body>
<?php
require '../src/QueryPath/QueryPath.php';

print qp('./parse_php.php', 'title')->text();
?>
</body>
</html>