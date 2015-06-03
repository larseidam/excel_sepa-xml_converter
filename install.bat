REM PHP Composer runterladen und installieren
php-binary\php.exe -r "readfile('https://getcomposer.org/installer');" | php
php-binary\php.exe composer.phar install

REM Konfigurationsdatei anlegen
copy config.ini.dist config.ini