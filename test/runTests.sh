#!/bin/bash

phpunit=/Applications/MAMP/bin/php5/bin/phpunit
$phpunit Tests/
rm "db/qpTest.db"
rm "db/qpTest2.db"
