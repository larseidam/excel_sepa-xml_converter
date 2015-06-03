#!/bin/bash

php -r "readfile('https://getcomposer.org/installer');" | php
php composer.phar install
cp config.ini.dist config.ini
sed -i 's/\\\\/\//g' config.ini