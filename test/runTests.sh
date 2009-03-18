#!/bin/bash

phpunit=/Applications/MAMP/bin/php5/bin/phpunit

$phpunit CssEventParserTests CssEventTests.php
$phpunit QueryPathCssEventHandlerTests CssEventTests.php
#$phpunit BottomUpCssEventHandlerTests
$phpunit QueryPathTests
$phpunit QueryPathExtensionTests
#$phpunit QPListTests
#$phpunit QPTPLTests
#$phpunit QPDBTests
#$phpunit QPXMLTests