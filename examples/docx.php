<?php
/**
 * DocX Parser
 * 
 * For namespaces use | instead of :
 *
 * @package Examples
 * @author Emily Brand
 * @license LGPL The GNU Lesser GPL (LGPL) or an MIT-like license.
 * @see http://www.urbandictionary.com/
 */
require_once '../src/QueryPath/QueryPath.php';

//$path = 'http://eabrand.com/images/test.docx';

$path = 'docx_document.xml';

// http://www.php.net/manual/en/function.zip-open.php - For how to find the document.xml file

$qp = qp($path, 'w|body');

print $qp->firstChild('w|t')->text();