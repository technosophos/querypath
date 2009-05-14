#!/bin/bash

phpunit=/Applications/MAMP/bin/php5/bin/phpunit

#$phpunit CssEventParserTests CssEventTests.php
#$phpunit QueryPathCssEventHandlerTests CssEventTests.php
#$phpunit BottomUpCssEventHandlerTests
#$phpunit QueryPathTests
#$phpunit QueryPathEntitiesTests
#$phpunit QueryPathOptionsTests
#$phpunit QueryPathExtensionTests
#$phpunit QPListTests
#$phpunit QPTPLTests
#$phpunit QPDBTests
#$phpunit QPXMLTests

$phpunit Tests
rm "db/qpTest.db"
rm "db/qpTest2.db"

#$phpunit --help
