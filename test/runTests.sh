#!/bin/bash

phpunit=/Applications/MAMP/bin/php5/bin/phpunit
$phpunit Tests/QueryPathTest
rm "db/qpTest.db"
rm "db/qpTest2.db"
