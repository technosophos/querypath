<?php
/**
 * Using QueryPath to Generate an RSS feed.
 *
 * This file contains an example of how QueryPath can be used
 * to generate an RSS feed.
 * @package QueryPath
 * @subpackage Examples
 * @author M Butcher <matt@aleph-null.tv>
 * @license LGPL The GNU Lesser GPL (LGPL) or an MIT-like license.
 */
 
require_once '../src/QueryPath/QueryPath.php';

$rss_stub ='<?xml version="1.0"?>

';
$rss_item_stub = '';

qp();