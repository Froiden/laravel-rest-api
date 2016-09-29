#!/usr/bin/env bash

if [ ! -f "laravel/composer.json" ]; then
    rm -rf laravel
    composer create-project laravel/laravel
    cd laravel
    composer install --no-interaction
    composer require $PACKAGE_NAME:dev-master
    if [[ -v PACKAGE_PROVIDER ]]; then
        echo "$(awk '/'\''providers'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${PACKAGE_PROVIDER})',"; next }1' \
            config/app.php)" > config/app.php
    fi
    if [[ -v FACADES ]]; then
        echo "$(awk '/'\''aliases'\''[^\n]*?\[/ { print; print "'$(sed -e 's/\s*//g' <<<${FACADES})',"; next }1' \
            config/app.php)" > config/app.php
    fi
    cd ..
fi