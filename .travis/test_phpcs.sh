#!/usr/bin/env bash

set -ev

echo "Running phpcs"

if [ -x "$(command -v phpenv)" ]; then
    phpenv config-rm xdebug.ini
fi

composer create-project --no-dev squizlabs/php_codesniffer:^3.3 codesniffer

cd codesniffer

composer require --update-no-dev de-swebhosting/php-codestyle:dev-master

cd ..

./codesniffer/bin/phpcs --config-set installed_paths $PWD/codesniffer/vendor/de-swebhosting/php-codestyle/PhpCodeSniffer

./codesniffer/bin/phpcs --standard=PSRDefault Classes Configuration Tests ext_*.php --extensions=php
