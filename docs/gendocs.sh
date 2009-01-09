#!/bin/bash
#####################
# Generate PHP Docs #
#####################

phpdoc=/Applications/MAMP/bin/php5/bin/phpdoc

src='../src/QueryPath';
docs='../docs'
title='QueryPath'
format='HTML:frames:phpdoc.de'
#format='HTML:frames:earthli'
category='QueryPath'

$phpdoc -s on -d $src -ti "$title" -t $docs -o $format -dc "$category" -dn "$category" 
